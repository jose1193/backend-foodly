<?php

namespace App\Http\Controllers;
use App\Http\Controllers\BaseController as BaseController;
use App\Models\BusinessCombo;
use App\Models\BusinessMenu;
use App\Http\Requests\BusinessComboRequest;
use App\Http\Resources\BusinessComboResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Ramsey\Uuid\Uuid;


class BusinessComboController extends BaseController
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

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $cacheKey = "user_{$this->userId}_business_combos";
            
            $combos = $this->getCachedData($cacheKey, $this->cacheTime, function () {
                return BusinessCombo::whereHas('businessMenu.business', function ($query) {
                    $query->whereIn('id', $this->businessIds);
                })->get();
            });

            return response()->json([
                'business_combos' => BusinessComboResource::collection($combos)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching business combos: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching business combos'. $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BusinessComboRequest $request)
    {
        DB::beginTransaction();
        try {
            $validatedData = $request->validated();
            $this->authorizeBusinessMenu($validatedData['business_menu_id']);
            $validatedData['uuid'] = Uuid::uuid4()->toString();

            $combo = BusinessCombo::create($validatedData);

            $this->updateComboCache($combo);
            $this->updateAllCombosCache();

            DB::commit();
            return response()->json(new BusinessComboResource($combo), 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating business combo: ' . $e->getMessage());
            return response()->json(['error' => 'Error creating business combo'], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        try {
            $cacheKey = "business_combo_{$uuid}";
            $combo = $this->getCachedData($cacheKey, $this->cacheTime, function () use ($uuid) {
                return BusinessCombo::findOrFail($uuid);
            });

            $this->authorizeBusinessMenu($combo->business_menu_id);

            return response()->json(new BusinessComboResource($combo), 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Business combo not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching business combo: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching business combo'], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BusinessComboRequest $request, string $uuid)
    {
        DB::beginTransaction();
        try {
            $combo = BusinessCombo::findOrFail($uuid);
            $this->authorizeBusinessMenu($combo->business_menu_id);

            $validatedData = $request->validated();
            $combo->update($validatedData);

            $this->updateComboCache($combo);
            $this->updateAllCombosCache();

            DB::commit();
            return response()->json(new BusinessComboResource($combo), 200);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Business combo not found'], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating business combo: ' . $e->getMessage());
            return response()->json(['error' => 'Error updating business combo'], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        DB::beginTransaction();
        try {
            $combo = BusinessCombo::findOrFail($uuid);
            $this->authorizeBusinessMenu($combo->business_menu_id);

            $combo->delete();

            $this->invalidateComboCache($combo);
            $this->updateAllCombosCache();

            DB::commit();
            return response()->json(['message' => 'Business combo deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Business combo not found'], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting business combo: ' . $e->getMessage());
            return response()->json(['error' => 'Error deleting business combo'], 500);
        }
    }

    private function authorizeBusinessMenu($businessMenuId): void
    {
        $isUserBusinessMenu = BusinessMenu::whereHas('business', function ($query) {
            $query->whereIn('id', $this->businessIds);
        })->where('id', $businessMenuId)->exists();

        if (!$isUserBusinessMenu) {
            abort(403, 'The provided business_menu_id does not belong to the authenticated user');
        }
    }

    private function updateComboCache(BusinessCombo $combo): void
    {
        $cacheKey = "business_combo_{$combo->id}";
        $this->updateCache($cacheKey, $this->cacheTime, function () use ($combo) {
            return new BusinessComboResource($combo);
        });
    }



    private function invalidateComboCache(BusinessCombo $combo): void
    {
        $cacheKey = "business_combo_{$combo->id}";
        $this->invalidateCache($cacheKey);
    }



    private function updateAllCombosCache(): void
    {
        $cacheKey = "user_{$this->userId}_business_combos";
        $this->updateCache($cacheKey, $this->cacheTime, function () {
            return BusinessComboResource::collection(
                BusinessCombo::whereHas('businessMenu.business', function ($query) {
                    $query->whereIn('id', $this->businessIds);
                })->get()
            );
        });
    }
}