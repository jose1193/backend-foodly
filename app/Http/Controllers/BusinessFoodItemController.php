<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController as BaseController;
use App\Models\BusinessFoodItem;
use App\Models\BusinessFoodCategory;
use App\Http\Requests\BusinessFoodItemRequest;
use App\Http\Resources\BusinessFoodItemResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;

class BusinessFoodItemController extends BaseController
{
    protected int $cacheTime = 720; // 12 hours
    protected ?int $userId;
    protected array $businessIds = [];

    public function __construct()
    {
        $this->middleware('check.permission:Manager')->only(['index', 'store', 'update', 'destroy']);

        $this->middleware(function ($request, $next) {
            $this->userId = Auth::id();
            $this->businessIds = Auth::user()->businesses()->pluck('id')->toArray();
            return $next($request);
        });
    }

    public function index()
    {
        try {
            $cacheKey = "user_{$this->userId}_business_food_item";
            
            $foodItems = $this->getCachedData($cacheKey, $this->cacheTime, function () {
                return BusinessFoodItem::with('businessFoodCategory.businessMenu.business')
                    ->whereHas('businessFoodCategory.businessMenu.business', function ($query) {
                        $query->whereIn('id', $this->businessIds);
                    })->get();
            });

            return response()->json([
                'items' => BusinessFoodItemResource::collection($foodItems)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching business food items: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching business food items: '. $e->getMessage()], 500);
        }
    }

    public function store(BusinessFoodItemRequest $request)
    {
        DB::beginTransaction();
        try {
            $validatedData = $request->validated();
            $this->authorizeBusinessFoodCategory($validatedData['business_food_category_id']);
            $validatedData['uuid'] = Uuid::uuid4()->toString();

            $foodItem = BusinessFoodItem::create($validatedData);

            // Load relationships needed for the resource
            $foodItem->loadMissing('businessFoodCategory.businessMenu.business');

            $this->updateFoodItemCache($foodItem);
            $this->updateAllFoodItemCache();

            DB::commit();
            return response()->json(new BusinessFoodItemResource($foodItem), 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating business food item: ' . $e->getMessage());
            return response()->json(['error' => 'Error creating business food item: '. $e->getMessage()], 500);
        }
    }

    public function show(string $uuid)
    {
        try {
            $cacheKey = "business_food_item_{$uuid}";
            
            $foodItem = $this->getCachedData($cacheKey, $this->cacheTime, function () use ($uuid) {
                return BusinessFoodItem::with('businessFoodCategory.businessMenu.business')
                    ->whereHas('businessFoodCategory.businessMenu.business', function ($query) {
                        $query->whereIn('id', $this->businessIds);
                    })->where('uuid', $uuid)->firstOrFail();
            });

            return response()->json(new BusinessFoodItemResource($foodItem), 200);
        } catch (\Exception $e) {
            Log::error('Error fetching business food item: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching business food item: '. $e->getMessage()], 500);
        }
    }

    public function update(BusinessFoodItemRequest $request, string $uuid)
    {
        DB::beginTransaction();
        try {
            $foodItem = BusinessFoodItem::with('businessFoodCategory.businessMenu.business')
                ->where('uuid', $uuid)->firstOrFail();
            
            $validatedData = $request->validated();
            $this->authorizeBusinessFoodCategory($foodItem->business_food_category_id);
            $foodItem->update($validatedData);

            $this->updateFoodItemCache($foodItem);
            $this->updateAllFoodItemCache();

            DB::commit();
            return response()->json(new BusinessFoodItemResource($foodItem), 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating business food item: ' . $e->getMessage());
            return response()->json(['error' => 'Error updating business food item: '. $e->getMessage()], 500);
        }
    }

    public function destroy(string $uuid)
    {
        DB::beginTransaction();
        try {
            $foodItem = BusinessFoodItem::where('uuid', $uuid)->firstOrFail();
            $this->authorizeBusinessFoodCategory($foodItem->business_food_category_id);
            
            $foodItem->delete();

            $this->invalidateFoodItemCache($foodItem);
            $this->updateAllFoodItemCache();

            DB::commit();
            return response()->json(['message' => 'Business food item deleted successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting business food item: ' . $e->getMessage());
            return response()->json(['error' => 'Error deleting business food item: '. $e->getMessage()], 500);
        }
    }

    private function authorizeBusinessFoodCategory($businessFoodCategoryId): void
    {
        $isUserBusinessFoodCategory = BusinessFoodCategory::whereHas('businessMenu.business', function ($query) {
            $query->whereIn('id', $this->businessIds);
        })->where('id', $businessFoodCategoryId)->exists();

        if (!$isUserBusinessFoodCategory) {
            abort(403, 'The provided business_food_category_id does not belong to the authenticated user');
        }
    }

    private function updateFoodItemCache(BusinessFoodItem $foodItem): void
    {
        $cacheKey = "business_food_item_{$foodItem->uuid}";
        $this->updateCache($cacheKey, $this->cacheTime, function () use ($foodItem) {
            // Ensure relationships are loaded before creating the resource for caching
            $foodItem->loadMissing('businessFoodCategory.businessMenu.business');
            return new BusinessFoodItemResource($foodItem);
        });
    }

    private function invalidateFoodItemCache(BusinessFoodItem $foodItem): void
    {
        $cacheKey = "business_food_item_{$foodItem->uuid}";
        $this->invalidateCache($cacheKey);
    }

    private function updateAllFoodItemCache(): void
    {
        $cacheKey = "user_{$this->userId}_business_food_item";
        $this->updateCache($cacheKey, $this->cacheTime, function () {
            return BusinessFoodItemResource::collection(
                BusinessFoodItem::with('businessFoodCategory.businessMenu.business')
                    ->whereHas('businessFoodCategory.businessMenu.business', function ($query) {
                        $query->whereIn('id', $this->businessIds);
                    })->get()
            );
        });
    }
}