<?php

namespace App\Http\Controllers;
use App\Http\Controllers\BaseController as BaseController;
use Illuminate\Http\Request;
use App\Models\BusinessDrinkCategory;
use App\Models\BusinessMenu;
use App\Http\Requests\BusinessDrinkCategoryRequest;
use App\Http\Resources\BusinessDrinkCategoryResource;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class BusinessDrinkCategoryController extends BaseController
{
    protected int $cacheTime = 720;
    protected ?int $userId;
    protected array $businessIds = [];
    protected array $businessMenuIds = [];

    public function __construct()
{
    $this->middleware('check.permission:Manager')->only(['index', 'show', 'store', 'update', 'destroy']);

    $this->middleware(function ($request, $next) {
        $this->userId = Auth::id();
        $this->businessIds = Auth::user()->businesses()->pluck('id')->toArray();
        $this->businessMenuIds = BusinessMenu::whereIn('business_id', $this->businessIds)->pluck('id')->toArray();
        return $next($request);
    });
}
   public function index(Request $request): \Illuminate\Http\JsonResponse
{
    try {
        $categories = collect();
        foreach ($this->businessMenuIds as $businessMenuId) {
            $this->authorizeBusinessMenu($businessMenuId);

            $cacheKey = "business_menu_{$businessMenuId}_drink_categories";
            $menuCategories = $this->getCachedData($cacheKey, $this->cacheTime, function () use ($businessMenuId) {
                return BusinessDrinkCategory::where('business_menu_id', $businessMenuId)->get();
            });

            $categories = $categories->concat($menuCategories);
        }

        return response()->json([
            'business_drink_categories' => BusinessDrinkCategoryResource::collection($categories)
        ], 200);
    } catch (\Exception $e) {
        Log::error('Error fetching business drink categories: ' . $e->getMessage());
        return response()->json(['message' => 'Error fetching business drink categories: ' . $e->getMessage()], 500);
    }
}

  public function store(BusinessDrinkCategoryRequest $request): \Illuminate\Http\JsonResponse
{
    return DB::transaction(function () use ($request) {
        try {
            $this->authorizeBusinessMenu($request->business_menu_id);
            $category = $this->createBusinessDrinkCategory($request->validated());
            
            $this->updateCache("business_drink_category_{$category->uuid}", $this->cacheTime, function () use ($category) {
                return $category;
            });
            $this->updateAllCategoriesCache($category->business_menu_id);
            
            return response()->json(new BusinessDrinkCategoryResource($category), 201);
        } catch (\Exception $e) {
            Log::error('Error creating business drink category: ' . $e->getMessage());
            throw $e;
        }
    });
}



    public function show(string $uuid): \Illuminate\Http\JsonResponse
{
    try {
        $cacheKey = "business_drink_category_{$uuid}";
        $category = $this->getCachedData($cacheKey, $this->cacheTime, function () use ($uuid) {
            return BusinessDrinkCategory::where('uuid', $uuid)->firstOrFail();
        });

        $this->authorizeBusinessMenu($category->business_menu_id);

        return response()->json(new BusinessDrinkCategoryResource($category), 200);
    } catch (ModelNotFoundException $e) {
        return response()->json(['message' => 'Business drink category not found'], 404);
    } catch (\Exception $e) {
        Log::error('Error fetching business drink category: ' . $e->getMessage());
        return response()->json(['message' => 'Error fetching business drink category'], 500);
    }
}




   public function update(BusinessDrinkCategoryRequest $request, string $uuid): \Illuminate\Http\JsonResponse
{
    return DB::transaction(function () use ($request, $uuid) {
        try {
            $category = BusinessDrinkCategory::where('uuid', $uuid)->firstOrFail();
            $this->authorizeBusinessMenu($category->business_menu_id);
            $category->update($request->validated());

            $this->updateCache("business_drink_category_{$category->uuid}", $this->cacheTime, function () use ($category) {
                return $category;
            });
            $this->updateAllCategoriesCache($category->business_menu_id);

            return response()->json(new BusinessDrinkCategoryResource($category), 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Business drink category not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error updating business drink category: ' . $e->getMessage());
            throw $e;
        }
    });
}



   public function destroy(string $uuid): \Illuminate\Http\JsonResponse
{
    return DB::transaction(function () use ($uuid) {
        try {
            $category = BusinessDrinkCategory::where('uuid', $uuid)->firstOrFail();
            $this->authorizeBusinessMenu($category->business_menu_id);
            $category->delete();

            $cacheKey = "business_drink_category_{$category->uuid}";
            $this->invalidateCache($cacheKey);
            $this->updateAllCategoriesCache($category->business_menu_id);
            return response()->json(['message' => 'Business drink category deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Business drink category not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting business drink category: ' . $e->getMessage());
            throw $e;
        }
    });
}

    private function createBusinessDrinkCategory(array $data): BusinessDrinkCategory
    {
        $data['uuid'] = Uuid::uuid4()->toString();
        return BusinessDrinkCategory::create($data);
    }

    

    private function updateAllCategoriesCache($businessMenuId): void
{
    $cacheKey = "business_menu_{$businessMenuId}_drink_categories";
    $this->refreshCache($cacheKey, $this->cacheTime, function () use ($businessMenuId) {
        return BusinessDrinkCategory::where('business_menu_id', $businessMenuId)->get();
    });
}



    private function authorizeBusinessMenu($businessMenuId): void
{
    $isUserBusinessMenu = BusinessMenu::whereIn('business_id', $this->businessIds)
        ->where('id', $businessMenuId)
        ->exists();

    if (!$isUserBusinessMenu) {
        abort(403, 'The provided business_menu_id does not belong to the authenticated user');
    }
}


}