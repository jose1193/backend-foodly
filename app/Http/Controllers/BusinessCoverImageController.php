<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;
use App\Models\BusinessCoverImage;
use App\Models\User;
use App\Http\Requests\BusinessCoverImageRequest;
use App\Http\Resources\BusinessCoverImageResource;
use Ramsey\Uuid\Uuid;
use App\Http\Requests\UpdateBusinessCoverImageRequest;

use App\Helpers\ImageHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Routing\Controller as BaseController;

class BusinessCoverImageController extends BaseController
{

     // PERMISSIONS USERS
    public function __construct()
{
   $this->middleware('check.permission:Manager')->only(['index', 'store',  'update', 'destroy']);

}

   public function index()
{
    try {
        $user = auth()->user();

        // Cargar los negocios del usuario
        $user->load('businesses');

        // Recopilar los IDs de los negocios
        $businessIds = $user->businesses->pluck('id')->toArray();

        // Generar la clave de caché utilizando los IDs de los negocios
        $cacheKey = 'business_' . implode('_', $businessIds) . '_cover_images';

        $groupedCoverImages = $this->getCachedData($cacheKey, 720, function () use ($user) {
            $user->load('businesses.coverImages');

            $groupedCoverImages = [];

            $user->businesses->each(function ($business) use ($user, &$groupedCoverImages) {
                if ($business->user_id === $user->id) {
                    // Agrupar las imágenes por el nombre del negocio y usar el Resource para la transformación de datos
                    $groupedCoverImages[$business->business_name] = BusinessCoverImageResource::collection($business->coverImages);
                }
            });

            return $groupedCoverImages;
        });

        return response()->json($groupedCoverImages, 200);
    } catch (\Exception $e) {
        Log::error('Error fetching business cover images', [
            'error' => $e->getMessage(),
            'user_id' => auth()->id()
        ]);
        return response()->json(['message' => 'Error fetching business cover images. Please try again later.'], 500);
    }
}






  public function store(BusinessCoverImageRequest $request)
{
    DB::beginTransaction(); // Start the transaction
    try {
        $validatedData = $request->validated();

        // Verify and adjust the format of business_image_path
        $imagePaths = $validatedData['business_image_path'];
        if (!is_array($imagePaths)) {
            $imagePaths = [$imagePaths];  // Convert to array if not already
        }

        $businessImages = collect($imagePaths)->map(function ($image) use ($validatedData) {
            $storedImagePath = ImageHelper::storeAndResize($image, 'public/business_photos');

            return BusinessCoverImage::create([
                'business_image_path' => $storedImagePath,
                'business_id' => $validatedData['business_id'],
                'business_image_uuid' => Uuid::uuid4()->toString(),
            ]);
        })->map(function ($businessCoverImage) {
            return new BusinessCoverImageResource($businessCoverImage);
        });

        DB::commit(); // Confirm the transaction if everything went well

        // Invalidate the cache for the user's business cover images
        $this->invalidateUserBusinessesCoverImageCache($validatedData['business_id']);

        // Cache the new response
        $cacheKey = 'business_' . $validatedData['business_id'] . '_cover_images';
        $this->putCachedData($cacheKey, $businessImages, 60); // Cache for 60 minutes

        return response()->json($businessImages, 200);
    } catch (\Exception $e) {
        DB::rollBack(); // Reverse the transaction in case of failure
        Log::error('Error storing business cover images: ' . $e->getMessage());
        return response()->json(['error' => 'Error storing business cover images: ' . $e->getMessage()], 500);
    }
}


public function updateImage(UpdateBusinessCoverImageRequest $request, $uuid)
{
    try {
        $businessCoverImage = BusinessCoverImage::where('business_image_uuid', $uuid)->firstOrFail();

        if ($request->hasFile('business_image_path')) {
            DB::transaction(function () use ($request, $businessCoverImage) {
                // Store and resize the new image
                $storedImagePath = ImageHelper::storeAndResize($request->file('business_image_path'), 'public/business_photos');

                // Delete the old image if it exists
                  if ($businessCoverImage->business_image_path) {
                    ImageHelper::deleteFileFromStorage($businessCoverImage->business_image_path);
                    }

                // Update the image path in the BusinessCoverImage model
                $businessCoverImage->business_image_path = $storedImagePath;
                $businessCoverImage->save();
            });
        }

        // Invalidate the cache for the user's businesses
        $this->invalidateUserBusinessesCoverImageCache($businessCoverImage->business->user_id);

        // Also invalidate the cache for the specific business cover images
        $this->invalidateCache('business_' . $businessCoverImage->business_id . '_cover_images');

        return response()->json(
            new BusinessCoverImageResource($businessCoverImage)
        );
    } catch (\Exception $e) {
        Log::error('Error updating business cover image: ' . $e->getMessage());
        return response()->json(['error' => 'Error updating business cover image: ' . $e->getMessage()], 500);
    }
}



public function show($uuid)
{
    try {
        // Validate the UUID format
        if (!Uuid::isValid($uuid)) {
            return response()->json(['error' => 'Invalid UUID format'], 400);
        }

        // Define the cache key based on the UUID
        $cacheKey = 'business_cover_image_' . $uuid;

        // Attempt to get the business cover image from the cache or the database
        $businessCoverImage = $this->getCachedData($cacheKey, 720, function () use ($uuid) {
            return BusinessCoverImage::where('business_image_uuid', $uuid)->firstOrFail();
        });

        // Return the business cover image resource
        return response()->json(new BusinessCoverImageResource($businessCoverImage));
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json(['error' => 'Business cover image not found'], 404);
    } catch (\Exception $e) {
        Log::error('Error retrieving business cover image: ' . $e->getMessage());
        return response()->json(['error' => 'Server error while retrieving business cover image'], 500);
    }
}

   public function destroy($uuid)
{
    if (!Uuid::isValid($uuid)) {
        return response()->json(['error' => 'Invalid UUID format'], 400);
    }

    try {
        DB::beginTransaction();

        $businessCoverImage = BusinessCoverImage::where('business_image_uuid', $uuid)->firstOrFail();
        
        $userId = $businessCoverImage->business->user_id;
        $businessId = $businessCoverImage->business_id;

        // Delete the image from storage
          if ($businessCoverImage->business_image_path) {
            ImageHelper::deleteFileFromStorage($businessCoverImage->business_image_path);
            }

        // Delete the business cover image
        $businessCoverImage->delete();

        DB::commit();

        // Invalidate the cache for the user's business cover images
        $this->invalidateUserBusinessesCoverImageCache($userId);

        // Also invalidate the cache for the specific business cover images
        $this->invalidateCache('business_' . $businessId . '_cover_images');

        // Invalidate the cache for the specific business cover image UUID
        $cacheKey = 'business_cover_image_' . $uuid;
        Cache::forget($cacheKey);

        return response()->json(['message' => 'Business cover image deleted successfully'], 200);
    } catch (ModelNotFoundException $e) {
        DB::rollBack();
        return response()->json(['message' => 'Business cover image not found'], 404);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error deleting business cover image: ' . $e->getMessage());
        return response()->json(['error' => 'Error deleting business cover image: ' . $e->getMessage()], 500);
    }
}



private function getCachedData($key, $minutes, \Closure $callback)
{
    return Cache::remember($key, now()->addMinutes($minutes), $callback);
}

private function putCachedData($key, $data, $minutes)
{
    Cache::put($key, $data, now()->addMinutes($minutes));
}

private function invalidateCache($key)
{
    Cache::forget($key);
}

private function invalidateUserBusinessesCoverImageCache($businessId)
{
    $cacheKey = 'business_' . $businessId . '_cover_images';
    $this->invalidateCache($cacheKey);
}



   
}
