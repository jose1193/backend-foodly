<?php

namespace App\Http\Controllers;
use App\Http\Controllers\BaseController as BaseController;
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
use Illuminate\Support\Facades\Auth;

class BusinessCoverImageController extends BaseController
{

    protected $cacheKey;
    
    protected $cacheTime = 720;
    protected $userId;


    public function __construct()
{
   $this->middleware('check.permission:Manager')->only(['index', 'store',  'update', 'destroy']);

   $this->middleware(function ($request, $next) {
            $this->userId = Auth::id();
            $this->cacheKey = 'user_' . $this->userId . '_business_cover_images';
            return $next($request);
        });


}

public function index()
{
    try {
        // Intentar recuperar los datos en caché
        $groupedCoverImages = $this->getCachedData($this->cacheKey, $this->cacheTime, function () {
            return $this->getAllBusinessCoverImages();
        });

        // Actualizar el caché con las imágenes de portada agrupadas
        $this->updateBusinessCoverImagesCache($groupedCoverImages);

        // Devolver una respuesta JSON con las imágenes de portada agrupadas
        return response()->json($groupedCoverImages, 200);
    } catch (\Exception $e) {
        // Registrar y manejar cualquier excepción que ocurra durante el proceso
        Log::error('Error in index function: ' . $e->getMessage(), [
            'user_id' => auth()->id()
        ]);
        return response()->json(['message' => 'An error occurred during processing'], 500);
    }
}



   private function getAllBusinessCoverImages()
{
    $user = auth()->user();
    $user->load('businesses.coverImages');

    $groupedCoverImages = [];

    $user->businesses->each(function ($business) use ($user, &$groupedCoverImages) {
        if ($business->user_id === $user->id) {
            $groupedCoverImages[$business->business_name] = BusinessCoverImageResource::collection($business->coverImages);
        }
    });

    return $groupedCoverImages;
}



private function updateBusinessCoverImagesCache($groupedCoverImages)
{
    $this->refreshCache($this->cacheKey, $this->cacheTime, function () use ($groupedCoverImages) {
        return $groupedCoverImages;
    });
}


  public function store(BusinessCoverImageRequest $request)
{
    DB::beginTransaction(); // Inicia la transacción
    try {
        $validatedData = $request->validated();

        // Verificar y ajustar el formato de business_image_path
        $imagePaths = $validatedData['business_image_path'];
        if (!is_array($imagePaths)) {
            $imagePaths = [$imagePaths];  // Convertir a array si no lo es
        }

        // Procesar y almacenar las imágenes
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

        // Actualizar el caché para cada nueva imagen
        $businessImages->each(function ($businessCoverImageResource) {
            $this->refreshCache('business_cover_uuid_' . $businessCoverImageResource->business_image_uuid, $this->cacheTime, function () use ($businessCoverImageResource) {
                return $businessCoverImageResource;
            });
        });

        // Cachear la nueva respuesta
        $this->updateBusinessCoverImagesCache($this->getAllBusinessCoverImages());
        
        DB::commit(); // Confirmar la transacción si todo salió bien

        return response()->json($businessImages, 201);
    } catch (\Exception $e) {
        DB::rollBack(); // Revertir la transacción en caso de fallo
        Log::error('Error storing business cover images: ' . $e->getMessage(), [
            'user_id' => auth()->id(),
            'trace' => $e->getTraceAsString()
        ]);
        return response()->json(['error' => 'Error storing business cover images. Please try again later.'], 500);
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

        // Cachear la nueva respuesta
         $this->refreshCache('business_cover_image_' . $uuid, $this->cacheTime, function () use ($businessCoverImage) {
            return new BusinessCoverImageResource($businessCoverImage);
        });
        $this->updateBusinessCoverImagesCache($this->getAllBusinessCoverImages());


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
        $businessCoverImage = $this->getCachedData($cacheKey, 360, function () use ($uuid) {
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

       
        $businessCoverImage->delete();

       

        // Also invalidate the cache for the specific business cover images
        $this->invalidateCache('business_cover_image_' . $uuid);

        // Invalidate the cache for the specific business cover image UUID
         $this->updateBusinessCoverImagesCache($this->getAllBusinessCoverImages());
        
        DB::commit();


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




   
}
