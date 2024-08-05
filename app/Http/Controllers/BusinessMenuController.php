<?php

namespace App\Http\Controllers;
use App\Http\Controllers\BaseController as BaseController;
use Illuminate\Http\Request;
use App\Models\BusinessMenu;
use App\Models\User;
use App\Models\Business;
use App\Http\Requests\BusinessMenuRequest;
use App\Http\Resources\BusinessMenuResource;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class BusinessMenuController extends BaseController
{
    protected array $cacheKeys;
    protected int $cacheTime = 720;
    protected ?int $userId;
    protected array $businessIds;


    public function __construct()
    {
        $this->middleware('check.permission:Manager')->only(['index', 'store', 'update', 'destroy']);

        $this->middleware(function ($request, $next) {
        // Solo aplicamos esto si el usuario está autenticado
        if (Auth::check()) {
            $this->userId = Auth::id();
            $user = User::findOrFail($this->userId);
            $this->businessIds = $user->businesses->pluck('id')->toArray();
            $this->cacheKeys = array_map(fn($businessId) => 
                "user_{$this->userId}_business_{$businessId}_business_menus", 
                $this->businessIds
            );
        }
        return $next($request);
    })->except(['show']);
    }


/**
     * Display a listing of the resource.
     */
    public function index(): \Illuminate\Http\JsonResponse
    {
        try {
            $businessMenus = $this->getBusinessMenus();
            return response()->json([
                'business_menus' => BusinessMenuResource::collection($businessMenus)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching business_menus: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching business_menus'. $e->getMessage()], 500);
        }
    }


    /**
     * Store a newly created resource in storage.
     */

    public function store(BusinessMenuRequest $request): \Illuminate\Http\JsonResponse
{
    return DB::transaction(function () use ($request) {
        try {
            $this->authorizeBusiness($request->business_id);
            $businessMenu = $this->createBusinessMenu($request->validated());
            
            $this->updateCache("business_menu_{$businessMenu->uuid}", $this->cacheTime, function () use ($businessMenu) {
                return $businessMenu;
            });
            $this->updateAllBusinessMenusCache();
            
            return response()->json(new BusinessMenuResource($businessMenu), 200);
        } catch (\Exception $e) {
            Log::error('Error creating business_menu: ' . $e->getMessage());
            throw $e;
        }
    });
}

    private function createBusinessMenu(array $data): BusinessMenu
    {
        $data['uuid'] = Uuid::uuid4()->toString();
        return BusinessMenu::create($data);
    }


    /**
     * Display the specified resource.
     */
    public function show(string $uuid): \Illuminate\Http\JsonResponse
    {
        try {
            $cacheKey = "business_menu_{$uuid}";
            $businessMenu = Cache::remember($cacheKey, $this->cacheTime, function () use ($uuid) {
                return BusinessMenu::where('uuid', $uuid)->firstOrFail();
            });

          

            return response()->json(new BusinessMenuResource($businessMenu), 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Business menu not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching business menu: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching business menu'], 500);
        }
    }



    /**
     * Update the specified resource in storage.
     */
    public function update(BusinessMenuRequest $request, string $uuid): \Illuminate\Http\JsonResponse
{
    return DB::transaction(function () use ($request, $uuid) {
        try {
            $businessMenu = BusinessMenu::where('uuid', $uuid)->firstOrFail();
            $this->authorizeBusiness($businessMenu->business_id);
            $businessMenu->update($request->validated());

            $this->updateCache("business_menu_{$businessMenu->uuid}", $this->cacheTime, function () use ($businessMenu) {
                return $businessMenu;
            });
            $this->updateAllBusinessMenusCache();

            return response()->json(new BusinessMenuResource($businessMenu), 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Business menu not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error updating business menu: ' . $e->getMessage());
            throw $e;
        }
    });
}



    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid): \Illuminate\Http\JsonResponse
    {
        return DB::transaction(function () use ($uuid) {
            try {
                $businessMenu = BusinessMenu::where('uuid', $uuid)->firstOrFail();
                $this->authorizeBusiness($businessMenu->business_id);
                $businessMenu->delete();

                $cacheKey = "business_menu_{$businessMenu->uuid}";
                $this->invalidateCache($cacheKey);
               
                $this->updateAllBusinessMenusCache();
                return response()->json(['message' => 'Business menu deleted successfully'], 200);
            } catch (ModelNotFoundException $e) {
                return response()->json(['message' => 'Business menu not found'], 404);
            } catch (\Exception $e) {
                Log::error('Error deleting business menu: ' . $e->getMessage());
                throw $e;
            }
        });
    }




    private function getBusinessMenus(): \Illuminate\Support\Collection
{
    $businessMenus = collect();
    foreach ($this->businessIds as $businessId) {
        $cacheKey = "user_{$this->userId}_business_{$businessId}_business_menus";
        $menus = $this->getCachedData($cacheKey, $this->cacheTime, function () use ($businessId) {
            return BusinessMenu::where('business_id', $businessId)->get();
        });
        $businessMenus = $businessMenus->concat($menus);
    }
    return $businessMenus;
}





    public function updateAllBusinessMenusCache(): void
{
    $this->refreshCache('all_business_menus', $this->cacheTime, function () {
        return BusinessMenu::all();
    });
}


    private function authorizeBusiness($businessId): void
    {
        $isUserBusiness = Business::where('user_id', $this->userId)->where('id', $businessId)->exists();

        if (!$isUserBusiness) {
            abort(403, 'The provided business_id does not belong to the authenticated user');
        }
    }


}