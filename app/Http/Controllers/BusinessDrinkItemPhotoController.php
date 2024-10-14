<?php
namespace App\Http\Controllers;
use App\Http\Controllers\BaseController as BaseController;
use Illuminate\Http\Request;
use App\Models\BusinessDrinkItemPhoto;
use App\Models\BusinessDrinkItem;
use App\Http\Resources\BusinessDrinkItemPhotoResource;
use App\Http\Requests\BusinessDrinkItemPhotoRequest;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Ramsey\Uuid\Uuid;
use App\Helpers\ImageHelper;

class BusinessDrinkItemPhotoController extends BaseController
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
            $businessDrinkItemIds = BusinessDrinkItem::whereHas('businessDrinkCategory.businessMenu.business', function ($query) {
                $query->whereIn('id', $this->businessIds);
            })->pluck('id')->toArray();

            $cacheKey = "user_{$this->userId}_business_drink_item_photos";

            $photos = $this->getCachedData($cacheKey, $this->cacheTime, function () use ($businessDrinkItemIds) {
                return BusinessDrinkItemPhoto::whereIn('business_drink_item_id', $businessDrinkItemIds)->get();
            });

            return response()->json([
                'business_drink_reference_photos' => BusinessDrinkItemPhotoResource::collection($photos)
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching business drink item photos: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching business drink item photos'. $e->getMessage()], 500);
        }
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(BusinessDrinkItemPhotoRequest $request)
{
    DB::beginTransaction();
    try {
        $this->authorizeBusinessDrinkItem($request->business_drink_item_id);

        $validatedData = $request->validated();

        // Ensure business_drink_photo_url is always an array
        $photoUrls = $validatedData['business_drink_photo_url'];
        if (!is_array($photoUrls)) {
            $photoUrls = [$photoUrls];
        }
        $validatedData['business_drink_photo_url'] = $photoUrls;

        $drinkItemPhotos = collect($photoUrls)->map(function ($image) use ($validatedData) {
            $storedImagePath = ImageHelper::storeAndResize($image, 'public/business_drink_item_photos');

            return BusinessDrinkItemPhoto::create([
                'business_drink_photo_url' => $storedImagePath,
                'business_drink_item_id' => $validatedData['business_drink_item_id'],
                'uuid' => Uuid::uuid4()->toString(),
            ]);
        });

        $businessDrinkItemPhotoResources = BusinessDrinkItemPhotoResource::collection($drinkItemPhotos);

        $businessDrinkItemPhotoResources->each(function ($businessDrinkItemPhotoResource) {
            $this->updateCache("business_drink_item_photo_{$businessDrinkItemPhotoResource->uuid}", $this->cacheTime, function () use ($businessDrinkItemPhotoResource) {
                return $businessDrinkItemPhotoResource;
            });
        });
        $this->invalidateCache("user_{$this->userId}_business_drink_item_photos");
        $this->updateAllPhotosCache($validatedData['business_drink_item_id']);

        DB::commit();

        return response()->json([
            'business_drink_reference_photos' => $businessDrinkItemPhotoResources
        ], 200);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error creating business drink item photos: ' . $e->getMessage());
        return response()->json(['error' => 'Error creating business drink item photos: ' . $e->getMessage()], 500);
    }
}
    /**
     * Display the specified resource.
     */
    public function show(string $uuid)
    {
        try {
            $cacheKey = "business_drink_item_photo_{$uuid}";
            $photo = $this->getCachedData($cacheKey, $this->cacheTime, function () use ($uuid) {
                return BusinessDrinkItemPhoto::where('uuid', $uuid)->firstOrFail();
            });

            $this->authorizeBusinessDrinkItem($photo->business_drink_item_id);

            return response()->json(new BusinessDrinkItemPhotoResource($photo), 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Business drink item photo not found'], 404);
        } catch (\Exception $e) {
            Log::error('Error fetching business drink item photo: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching business drink item photo'], 500);
        }
    }
    /**
     * Update the specified resource in storage.
     */
   public function update(BusinessDrinkItemPhotoRequest $request, string $uuid)
{
    try {
        $businessDrinkItemPhoto = BusinessDrinkItemPhoto::where('uuid', $uuid)->firstOrFail();
        $this->authorizeBusinessDrinkItem($businessDrinkItemPhoto->business_drink_item_id);

        if ($request->hasFile('business_drink_photo_url')) {
            DB::transaction(function () use ($request, $businessDrinkItemPhoto) {
                // Store and resize the new image
                $storedImagePath = ImageHelper::storeAndResize(
                    $request->file('business_drink_photo_url'),
                    'public/business_drink_item_photos'
                );

                // Delete the old image if it exists
                if ($businessDrinkItemPhoto->business_drink_photo_url) {
                    ImageHelper::deleteFileFromStorage($businessDrinkItemPhoto->business_drink_photo_url);
                }

                // Update the image path in the BusinessDrinkItemPhoto model
                $businessDrinkItemPhoto->business_drink_photo_url = $storedImagePath;
                $businessDrinkItemPhoto->save();
            });
        }

        // Only update the other fields if necessary
        $validatedData = $request->except(['business_drink_photo_url']);
        $businessDrinkItemPhoto->update($validatedData);

        // Cache the new response
        $this->updateCache("business_drink_item_photo_{$uuid}", $this->cacheTime, function () use ($businessDrinkItemPhoto) {
            return new BusinessDrinkItemPhotoResource($businessDrinkItemPhoto);
        });

        // Refresh the cache for all photos of this drink item
        $this->updateAllPhotosCache($businessDrinkItemPhoto->business_drink_item_id);

        return response()->json(new BusinessDrinkItemPhotoResource($businessDrinkItemPhoto), 200);
    } catch (ModelNotFoundException $e) {
        return response()->json(['message' => 'Business drink item photo not found'], 404);
    } catch (\Exception $e) {
        Log::error('Error updating business drink item photo: ' . $e->getMessage());
        return response()->json(['error' => 'Error updating business drink item photo: ' . $e->getMessage()], 500);
    }
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        return DB::transaction(function () use ($uuid) {
            try {
                $photo = BusinessDrinkItemPhoto::where('uuid', $uuid)->firstOrFail();
                $this->authorizeBusinessDrinkItem($photo->business_drink_item_id);
                if ($photo->business_drink_photo_url) {
                ImageHelper::deleteFileFromStorage($photo->business_drink_photo_url);
            }
                $photo->delete();

                $this->invalidateCache($photo);
                
                return response()->json(['message' => 'Business drink item photo deleted successfully'], 200);
            } catch (ModelNotFoundException $e) {
                return response()->json(['message' => 'Business drink item photo not found'], 404);
            } catch (\Exception $e) {
                Log::error('Error deleting business drink item photo: ' . $e->getMessage());
                throw $e;
            }
        });
    }


    private function updateAllPhotosCache($businessDrinkItemId): void
    {
        $cacheKey = "business_drink_item_{$businessDrinkItemId}_photos";
        $this->updateCache($cacheKey, $this->cacheTime, function () use ($businessDrinkItemId) {
            return BusinessDrinkItemPhotoResource::collection(
                BusinessDrinkItemPhoto::where('business_drink_item_id', $businessDrinkItemId)->get()
            );
        });
    }

     private function authorizeBusinessDrinkItem($businessDrinkItemId): void
    {
        $isUserBusinessDrinkItem = BusinessDrinkItem::whereHas('businessDrinkCategory.businessMenu.business', function ($query) {
            $query->whereIn('id', $this->businessIds);
        })->where('id', $businessDrinkItemId)->exists();

        if (!$isUserBusinessDrinkItem) {
            abort(403, 'The provided business_food_item_id does not belong to the authenticated user');
        }
    }

   
}
