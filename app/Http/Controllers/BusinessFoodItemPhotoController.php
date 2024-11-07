<?php

namespace App\Http\Controllers;
use App\Http\Controllers\BaseController as BaseController;
use Illuminate\Http\Request;
use App\Models\BusinessFoodItemPhoto;
use App\Models\BusinessFoodItem;
use App\Http\Resources\BusinessFoodItemPhotoResource;
use App\Http\Requests\BusinessFoodItemPhotoRequest;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Ramsey\Uuid\Uuid;
use App\Helpers\ImageHelper;

class BusinessFoodItemPhotoController extends BaseController
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

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $businessFoodItemIds = BusinessFoodItem::whereHas('businessFoodCategory.businessMenu.business', function ($query) {
                $query->whereIn('id', $this->businessIds);
            })->pluck('id')->toArray();

            $cacheKey = "user_{$this->userId}_business_food_item_photos";

            $photos = $this->getCachedData($cacheKey, $this->cacheTime, function () use ($businessFoodItemIds) {
                return BusinessFoodItemPhoto::whereIn('business_food_item_id', $businessFoodItemIds)->get();
            });

            return response()->json([
                'business_food_reference_photos' => BusinessFoodItemPhotoResource::collection($photos)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching business food item photos: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching business food item photos'. $e->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BusinessFoodItemPhotoRequest $request)
{
    DB::beginTransaction();
    try {
        $this->authorizeBusinessFoodItem($request->business_food_item_id);
        
        $validatedData = $request->validated();
        
        // Ensure business_food_photo_url is always an array
        $photoUrls = $validatedData['business_food_photo_url'];
        if (!is_array($photoUrls)) {
            $photoUrls = [$photoUrls];
        }
        $validatedData['business_food_photo_url'] = $photoUrls;
        
        $foodItemPhotos = collect($photoUrls)->map(function ($image) use ($validatedData) {
            $storedImagePath = ImageHelper::storeAndResize($image, 'public/business_food_item_photos');
            
            return BusinessFoodItemPhoto::create([
                'business_food_photo_url' => $storedImagePath,
                'business_food_item_id' => $validatedData['business_food_item_id'],
                'uuid' => Uuid::uuid4()->toString(),
            ]);
        });

        $businessFoodItemPhotoResources = BusinessFoodItemPhotoResource::collection($foodItemPhotos);
        
        $businessFoodItemPhotoResources->each(function ($businessFoodItemPhotoResource) {
            $this->updateCache("business_food_item_photo_{$businessFoodItemPhotoResource->uuid}", $this->cacheTime, function () use ($businessFoodItemPhotoResource) {
                return $businessFoodItemPhotoResource;
            });
        });
        
        $this->invalidateCache("user_{$this->userId}_business_food_item_photos");
        $this->updateAllPhotosCache($validatedData['business_food_item_id']);
        
        DB::commit();
        
        return response()->json([
            'business_food_reference_photos' => $businessFoodItemPhotoResources
        ], 200);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error creating business food item photos: ' . $e->getMessage());
        return response()->json(['error' => 'Error creating business food item photos: ' . $e->getMessage()], 500);
    }
}

    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        try {
            $cacheKey = "business_food_item_photo_{$uuid}";
            $photo = $this->getCachedData($cacheKey, $this->cacheTime, function () use ($uuid) {
                return BusinessFoodItemPhoto::where('uuid', $uuid)->firstOrFail();
            });

            $this->authorizeBusinessFoodItem($photo->business_food_item_id);

            return response()->json(new BusinessFoodItemPhotoResource($photo), 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Business food item photo not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching business food item photo: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching business food item photo'], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */

      

public function update(BusinessFoodItemPhotoRequest $request, string $uuid)
{
    try {
        $businessFoodItemPhoto = BusinessFoodItemPhoto::where('uuid', $uuid)->firstOrFail();
        $this->authorizeBusinessFoodItem($businessFoodItemPhoto->business_food_item_id);

        DB::beginTransaction();

        $validatedData = $request->validated();
        
        // Store and resize the new image
        $storedImagePath = ImageHelper::storeAndResize(
            $validatedData['business_food_photo_url'],
            'public/business_food_item_photos'
        );

        // Delete the old image if it exists
        if ($businessFoodItemPhoto->business_food_photo_url) {
            ImageHelper::deleteFileFromStorage($businessFoodItemPhoto->business_food_photo_url);
        }

        // Update the image path and other fields
        $businessFoodItemPhoto->business_food_photo_url = $storedImagePath;
        
        if (isset($validatedData['business_food_item_id'])) {
            $businessFoodItemPhoto->business_food_item_id = $validatedData['business_food_item_id'];
        }

        $businessFoodItemPhoto->save();

        DB::commit();

        // Update cache
        $this->updateCache("business_food_item_photo_{$uuid}", $this->cacheTime, function () use ($businessFoodItemPhoto) {
            return new BusinessFoodItemPhotoResource($businessFoodItemPhoto);
        });

        // Refresh the cache for all photos of this food item
        $this->updateAllPhotosCache($businessFoodItemPhoto->business_food_item_id);

        return response()->json(new BusinessFoodItemPhotoResource($businessFoodItemPhoto), 200);
    } catch (ModelNotFoundException $e) {
        DB::rollBack();
        return response()->json(['message' => 'Business food item photo not found'], 404);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error updating business food item photo: ' . $e->getMessage());
        return response()->json(['error' => 'Error updating business food item photo: ' . $e->getMessage()], 500);
    }
    }

    public function update2(BusinessFoodItemPhotoRequest $request, string $uuid)
    {
        try {
            $businessFoodItemPhoto = BusinessFoodItemPhoto::where('uuid', $uuid)->firstOrFail();
            $this->authorizeBusinessFoodItem($businessFoodItemPhoto->business_food_item_id);

            DB::beginTransaction();

            if ($request->hasFile('business_food_photo_url')) {
                // Store and resize the new image
                $storedImagePath = ImageHelper::storeAndResize(
                    $request->file('business_food_photo_url'),
                    'public/business_food_item_photos'
                );

                // Delete the old image if it exists
                if ($businessFoodItemPhoto->business_food_photo_url) {
                    ImageHelper::deleteFileFromStorage($businessFoodItemPhoto->business_food_photo_url);
                }

                // Update the image path in the BusinessFoodItemPhoto model
                $businessFoodItemPhoto->business_food_photo_url = $storedImagePath;
            }

            // Update other fields if they exist in the request
            if ($request->has('business_food_item_id')) {
                $businessFoodItemPhoto->business_food_item_id = $request->business_food_item_id;
            }

            $businessFoodItemPhoto->save();

            DB::commit();

            // Update cache
            $this->updateCache("business_food_item_photo_{$uuid}", $this->cacheTime, function () use ($businessFoodItemPhoto) {
                return new BusinessFoodItemPhotoResource($businessFoodItemPhoto);
            });

            // Refresh the cache for all photos of this food item
            $this->updateAllPhotosCache($businessFoodItemPhoto->business_food_item_id);

            return response()->json(new BusinessFoodItemPhotoResource($businessFoodItemPhoto), 200);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Business food item photo not found'], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating business food item photo: ' . $e->getMessage());
            return response()->json(['error' => 'Error updating business food item photo: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
{
    return DB::transaction(function () use ($uuid) {
        try {
            $photo = BusinessFoodItemPhoto::where('uuid', $uuid)->firstOrFail();
            $this->authorizeBusinessFoodItem($photo->business_food_item_id);

             
           if ($photo->business_food_photo_url) {
                ImageHelper::deleteFileFromStorage($photo->business_food_photo_url);
            }
                $photo->delete();

            // Invalidate the cache
            $this->invalidateCache($photo);

            // Update the cache for all photos of this food item
            $this->updateAllPhotosCache($photo->business_food_item_id);

            return response()->json(['message' => 'Business food item photo deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Business food item photo not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error deleting business food item photo: ' . $e->getMessage());
            throw $e;
        }
    });
}

    private function updateAllPhotosCache($businessFoodItemId): void
    {
        $cacheKey = "business_food_item_{$businessFoodItemId}_photos";
        $this->updateCache($cacheKey, $this->cacheTime, function () use ($businessFoodItemId) {
            return BusinessFoodItemPhotoResource::collection(
                BusinessFoodItemPhoto::where('business_food_item_id', $businessFoodItemId)->get()
            );
        });
    }

    private function authorizeBusinessFoodItem($businessFoodItemId): void
    {
        $isUserBusinessFoodItem = BusinessFoodItem::whereHas('businessFoodCategory.businessMenu.business', function ($query) {
            $query->whereIn('id', $this->businessIds);
        })->where('id', $businessFoodItemId)->exists();

        if (!$isUserBusinessFoodItem) {
            abort(403, 'The provided business_food_item_id does not belong to the authenticated user');
        }
    }
}