<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController as BaseController;
use App\Models\BusinessDrinkItem;
use App\Models\BusinessDrinkCategory;
use App\Http\Requests\BusinessDrinkItemRequest;
use App\Http\Resources\BusinessDrinkItemResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;

class BusinessDrinkItemController extends BaseController
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
            $cacheKey = "user_{$this->userId}_business_drink_item";
            
            $drinkItems = $this->getCachedData($cacheKey, $this->cacheTime, function () {
                return BusinessDrinkItem::whereHas('businessDrinkCategory.businessMenu.business', function ($query) {
                    $query->whereIn('id', $this->businessIds);
                })->get();
            });

            return response()->json([
                'items' => BusinessDrinkItemResource::collection($drinkItems)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching business drink items: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching business drink items: '. $e->getMessage()], 500);
        }
    }

    public function store(BusinessDrinkItemRequest $request)
    {
        DB::beginTransaction();
        try {
            $validatedData = $request->validated();
            $this->authorizeBusinessDrinkCategory($validatedData['business_drink_category_id']);
            $validatedData['uuid'] = Uuid::uuid4()->toString();

            $drinkItem = BusinessDrinkItem::create($validatedData);

            $this->updateDrinkItemCache($drinkItem);
            $this->updateAllDrinkItemCache();

            DB::commit();
            return response()->json(new BusinessDrinkItemResource($drinkItem), 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating business drink item: ' . $e->getMessage());
            return response()->json(['error' => 'Error creating business drink item: '. $e->getMessage()], 500);
        }
    }

    public function show(string $uuid)
    {
        try {
            $cacheKey = "business_drink_item_{$uuid}";
        
            $drinkItem = $this->getCachedData($cacheKey, $this->cacheTime, function () use ($uuid) {
                return BusinessDrinkItem::whereHas('businessDrinkCategory.businessMenu.business', function ($query) {
                    $query->whereIn('id', $this->businessIds);
                })->where('uuid', $uuid)->firstOrFail();
            });

            return response()->json(new BusinessDrinkItemResource($drinkItem), 200);
        } catch (\Exception $e) {
            Log::error('Error fetching business drink item: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching business drink item: '. $e->getMessage()], 500);
        }
    }

    public function update(BusinessDrinkItemRequest $request, string $uuid)
    {
        DB::beginTransaction();
        try {
            $drinkItem = BusinessDrinkItem::where('uuid', $uuid)->firstOrFail();
            
            $validatedData = $request->validated();
            $this->authorizeBusinessDrinkCategory($validatedData['business_drink_category_id']);
            $drinkItem->update($validatedData);

            $this->updateDrinkItemCache($drinkItem);
            $this->updateAllDrinkItemCache();

            DB::commit();
            return response()->json(new BusinessDrinkItemResource($drinkItem), 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating business drink item: ' . $e->getMessage());
            return response()->json(['error' => 'Error updating business drink item: '. $e->getMessage()], 500);
        }
    }

    public function destroy(string $uuid)
    {
        DB::beginTransaction();
        try {
           $drinkItem = BusinessDrinkItem::where('uuid', $uuid)->firstOrFail();
           $this->authorizeBusinessDrinkCategory($drinkItem->business_drink_category_id);
            
            $drinkItem->delete();

            $this->invalidateDrinkItemCache($drinkItem);
            $this->updateAllDrinkItemCache();

            DB::commit();
            return response()->json(['message' => 'Business drink item deleted successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting business drink item: ' . $e->getMessage());
            return response()->json(['error' => 'Error deleting business drink item: '. $e->getMessage()], 500);
        }
    }

    private function authorizeBusinessDrinkCategory($businessDrinkCategoryId): void
    {
        $isUserBusinessDrinkCategory = BusinessDrinkCategory::whereHas('businessMenu.business', function ($query) {
            $query->whereIn('id', $this->businessIds);
        })->where('id', $businessDrinkCategoryId)->exists();

        if (!$isUserBusinessDrinkCategory) {
            abort(403, 'The provided business_drink_category_id does not belong to the authenticated user');
        }
    }

    private function updateDrinkItemCache(BusinessDrinkItem $drinkItem): void
    {
        $cacheKey = "business_drink_item_{$drinkItem->uuid}";
        $this->updateCache($cacheKey, $this->cacheTime, function () use ($drinkItem) {
            return $drinkItem;
        });
    }

    private function invalidateDrinkItemCache(BusinessDrinkItem $drinkItem): void
    {
        $cacheKey = "business_drink_item_{$drinkItem->uuid}";
        $this->invalidateCache($cacheKey);
    }

    private function updateAllDrinkItemCache(): void
    {
        $cacheKey = "user_{$this->userId}_business_drink_item";
        $this->updateCache($cacheKey, $this->cacheTime, function () {
            return BusinessDrinkItemResource::collection(
                BusinessDrinkItem::whereHas('businessDrinkCategory.businessMenu.business', function ($query) {
                    $query->whereIn('id', $this->businessIds);
                })->get()
            );
        });
    }
}