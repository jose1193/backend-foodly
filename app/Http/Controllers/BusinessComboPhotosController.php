<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController as BaseController;
use Illuminate\Http\Request;
use App\Models\BusinessComboPhoto;
use App\Models\BusinessCombo;
use App\Http\Resources\BusinessComboPhotoResource;
use App\Http\Requests\BusinessComboPhotoRequest;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Ramsey\Uuid\Uuid;
use App\Helpers\ImageHelper;

class BusinessComboPhotosController extends BaseController
{
    protected int $cacheTime = 720;
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
            $businessCombosIds = BusinessCombo::whereHas('businessMenu.business', function ($query) {
                $query->whereIn('id', $this->businessIds);
            })->pluck('id')->toArray();

            $cacheKey = "user_{$this->userId}_business_combo_photos";

            $photos = $this->getCachedData($cacheKey, $this->cacheTime, function () use ($businessCombosIds) {
                return BusinessComboPhoto::whereIn('business_combos_id', $businessCombosIds)->get();
            });

            return response()->json([
                'business_combo_photos' => BusinessComboPhotoResource::collection($photos)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching business combo photos: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching business combo photos' . $e->getMessage()], 500);
        }
    }

    public function store(BusinessComboPhotoRequest $request)
    {
        DB::beginTransaction();
        try {
        $this->authorizeBusinessCombo($request->business_combos_id);

        $validatedData = $request->validated();

        // Ensure business_combos_photo_url is always an array
        $photoUrls = $validatedData['business_combos_photo_url'];
        if (!is_array($photoUrls)) {
            $photoUrls = [$photoUrls];
        }
        $validatedData['business_combos_photo_url'] = $photoUrls;

        $comboPhotos = collect($photoUrls)->map(function ($image) use ($validatedData) {
            $storedImagePath = ImageHelper::storeAndResize($image, 'public/business_combo_photos');

            return BusinessComboPhoto::create([
                'business_combos_photo_url' => $storedImagePath,
                'business_combos_id' => $validatedData['business_combos_id'],
                'uuid' => Uuid::uuid4()->toString(),
            ]);
        });

        $businessComboPhotoResources = BusinessComboPhotoResource::collection($comboPhotos);

        $businessComboPhotoResources->each(function ($businessComboPhotoResource) {
            $this->updateCache("business_combo_photo_{$businessComboPhotoResource->uuid}", $this->cacheTime, function () use ($businessComboPhotoResource) {
                return $businessComboPhotoResource;
            });
        });
        $this->invalidateCache("user_{$this->userId}_business_combo_photos");
        $this->updateAllPhotosCache($validatedData['business_combos_id']);

        DB::commit();

        return response()->json([
            'business_combo_photos' => $businessComboPhotoResources
        ], 200);
        } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error creating business combo photos: ' . $e->getMessage());
        return response()->json(['error' => 'Error creating business combo photos: ' . $e->getMessage()], 500);
        }
    }

    public function show(string $uuid)
    {
        try {
            $cacheKey = "business_combo_photo_{$uuid}";
            $photo = $this->getCachedData($cacheKey, $this->cacheTime, function () use ($uuid) {
                return BusinessComboPhoto::where('uuid', $uuid)->firstOrFail();
            });

            $this->authorizeBusinessCombo($photo->business_combos_id);

            return response()->json(new BusinessComboPhotoResource($photo), 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Business combo photo not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching business combo photo: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching business combo photo'], 500);
        }
    }

    

public function update(BusinessComboPhotoRequest $request, string $uuid)
{
    try {
        $businessComboPhoto = BusinessComboPhoto::where('uuid', $uuid)->firstOrFail();
        $this->authorizeBusinessCombo($businessComboPhoto->business_combos_id);

        DB::beginTransaction();

        if ($request->hasFile('business_combos_photo_url')) {
            // Store and resize the new image
            $storedImagePath = ImageHelper::storeAndResize(
                $request->file('business_combos_photo_url'),
                'public/business_combo_photos'
            );

            // Delete the old image if it exists
            if ($businessComboPhoto->business_combos_photo_url) {
                ImageHelper::deleteFileFromStorage($businessComboPhoto->business_combos_photo_url);
            }

            // Update the image path in the BusinessComboPhoto model
            $businessComboPhoto->business_combos_photo_url = $storedImagePath;
        }

        // Update other fields if they exist in the request
        if ($request->has('business_combos_id')) {
            $businessComboPhoto->business_combos_id = $request->business_combos_id;
        }

        $businessComboPhoto->save();

        DB::commit();

        // Update cache
        $this->updateCache("business_combo_photo_{$uuid}", $this->cacheTime, function () use ($businessComboPhoto) {
            return new BusinessComboPhotoResource($businessComboPhoto);
        });

        // Refresh the cache for all photos of this combo
        $this->updateAllPhotosCache($businessComboPhoto->business_combos_id);

        return response()->json(new BusinessComboPhotoResource($businessComboPhoto), 200);
    } catch (ModelNotFoundException $e) {
        DB::rollBack();
        return response()->json(['message' => 'Business combo photo not found'], 404);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error updating business combo photo: ' . $e->getMessage());
        return response()->json(['error' => 'Error updating business combo photo: ' . $e->getMessage()], 500);
    }
}


    public function destroy(string $uuid)
    {
        return DB::transaction(function () use ($uuid) {
            try {
                $photo = BusinessComboPhoto::where('uuid', $uuid)->firstOrFail();
                $this->authorizeBusinessCombo($photo->business_combos_id);
                 // Delete the old image if it exists
            if ($photo->business_combos_photo_url) {
                ImageHelper::deleteFileFromStorage($photo->business_combos_photo_url);
            }
                $photo->delete();

                $this->invalidateCache($photo);

                return response()->json(['message' => 'Business combo photo deleted successfully'], 200);
            } catch (ModelNotFoundException $e) {
                return response()->json(['message' => 'Business combo photo not found'], 404);
            } catch (\Exception $e) {
                Log::error('Error deleting business combo photo: ' . $e->getMessage());
                throw $e;
            }
        });
    }

    private function updateAllPhotosCache($businessComboId): void
    {
        $cacheKey = "business_combo_{$businessComboId}_photos";
        $this->updateCache($cacheKey, $this->cacheTime, function () use ($businessComboId) {
            return BusinessComboPhotoResource::collection(
                BusinessComboPhoto::where('business_combos_id', $businessComboId)->get()
            );
        });
    }

    private function authorizeBusinessCombo($businessComboId): void
    {
        $isUserBusinessCombo = BusinessCombo::whereHas('businessMenu.business', function ($query) {
            $query->whereIn('id', $this->businessIds);
        })->where('id', $businessComboId)->exists();

        if (!$isUserBusinessCombo) {
            abort(403, 'The provided business_combo_id does not belong to the authenticated user');
        }
    }
}
