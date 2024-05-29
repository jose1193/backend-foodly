<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Promotion;
use App\Models\PromotionImage;
use App\Models\User;
use App\Http\Requests\PromotionCoverImageRequest;
use App\Http\Resources\PromotionImageResource;
use Ramsey\Uuid\Uuid;
use App\Http\Requests\UpdatePromotionImageRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Helpers\ImageHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Routing\Controller as BaseController;

class PromotionCoverImageController extends BaseController
{
    // PERMISSIONS USERS
    public function __construct()
{
   $this->middleware('check.permission:Manager')->only(['index','create', 'store', 'edit', 'update', 'destroy']);

}


    /**
     * Display a listing of the resource.
     */
   public function index()
{
    try {
        // Obtener el ID del usuario autenticado
        $userId = auth()->id();
        $cacheKey = $this->getPromotionImagesCacheKey($userId);

        // Intentar obtener los datos en caché
        $groupedPromotionImages = $this->getCachedData($cacheKey, 30, function() use ($userId) {
            // Obtener todos los negocios asociados al usuario autenticado con carga ansiosa de promociones e imágenes
            $businesses = User::findOrFail($userId)->businesses()->with('promotions.promotionImages')->get();

           
            $groupedPromotionImages = [];

            // Iterar sobre cada negocio y sus promociones para agrupar las imágenes de promoción
            foreach ($businesses as $business) {
                foreach ($business->promotions as $promotion) {
                    $promotionTitle = $promotion->promotion_title;
                    $promotionImages = $promotion->promotionImages;

                    $groupedPromotionImages[$promotionTitle] = PromotionImageResource::collection($promotionImages);
                }
            }

            return $groupedPromotionImages;
        });

        // Devolver todas las imágenes de promoción agrupadas por título de promoción como respuesta JSON
        return response()->json(['grouped_promotion_images' => $groupedPromotionImages], 200);
    } catch (\Exception $e) {
        Log::error('An error occurred while fetching promotion images: ' . $e->getMessage());
        return response()->json(['message' => 'Error fetching promotion images'], 500);
    }
}





    /**
     * Store a newly created resource in storage.
     */
    public function store(PromotionCoverImageRequest $request)
{
    try {
        // Iniciar una transacción de base de datos
        DB::beginTransaction();

        // Validar la solicitud entrante
        $validatedData = $request->validated();
        $promotionImages = [];

        // Almacenar las imágenes de portada del negocio
        foreach ($validatedData['promotion_image_path'] as $image) {
            // Almacenar y redimensionar la imagen
            $storedImagePath = ImageHelper::storeAndResize($image, 'public/promotion_photos');

            // Crear una nueva instancia de PromotionImage y guardarla en la base de datos
            $promotionImage = PromotionImage::create([
                'promotion_image_path' => $storedImagePath,
                'promotion_id' => $validatedData['promotion_id'],
                'promotion_image_uuid' => Uuid::uuid4()->toString(),
            ]);

            // Crear una instancia de PromotionImageResource para la respuesta JSON
            $promotionImages[] = new PromotionImageResource($promotionImage);
        }

        // Confirmar la transacción
        DB::commit();

        // Invalidar la caché de imágenes de promoción del usuario
        $userId = auth()->id();
        $this->invalidateUserPromotionImagesCache($userId);

        return response()->json([
            'promotion_images' => $promotionImages,
        ]);
    } catch (\Exception $e) {
        // Revertir la transacción en caso de error
        DB::rollBack();
        Log::error('An error occurred while storing promotion images: ' . $e->getMessage());
        return response()->json(['error' => 'Error storing promotion images'], 500);
    }
}




    /**
     * Display the specified resource.
     */
    public function show($uuid)
{
    try {
      
        // Attempt to retrieve the promotion image from the cache
        $promotionImageResource = $this->getCachedData('promotion_image_' . $uuid, 30, function() use ($uuid) {
            // Find the promotion image by its UUID
           $promotionImage = PromotionImage::where('promotion_image_uuid', $uuid)->firstOrFail();


            // Check if the promotion image was found
            if (!$promotionImage) {
                throw new \Exception('Promotion image not found');
            }

            // Create a resource for the promotion image
            return new PromotionImageResource($promotionImage);
        });

        // Return the promotion image resource
        return response()->json(['promotion_image' => $promotionImageResource], 200);
    } catch (\Exception $e) {
        // Handle errors more thoroughly
        Log::error('An error occurred while retrieving promotion image: ' . $e->getMessage());
        return response()->json(['error' => 'Error retrieving promotion image'], 500);
    }
}




    /**
     * Update the specified resource in storage.
     */

     public function updateImage(UpdatePromotionImageRequest $request, $promotion_image_uuid)
{
    DB::beginTransaction();
    try {
        // Find the promotion image by its UUID
        $promotionImage = PromotionImage::where('promotion_image_uuid', $promotion_image_uuid)->firstOrFail();

        if ($request->hasFile('promotion_image_path')) {
            // Store and resize the new image
            $storedImagePath = ImageHelper::storeAndResize($request->file('promotion_image_path'), 'public/promotion_photos');

            // Delete the old image if it exists
            ImageHelper::deleteFileFromStorage($promotionImage->promotion_image_path);

            // Update the image path in the PromotionImage model
            $promotionImage->promotion_image_path = $storedImagePath;
            $promotionImage->save();
        }

        // Commit the transaction
        DB::commit();

        // Invalidate the cache for the updated promotion image
        $this->invalidateUserPromotionImagesCache(Auth::id());
        $this->invalidateCache('promotion_image_' . $promotion_image_uuid);

        return response()->json([
            'promotion_image' => new PromotionImageResource($promotionImage)
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('An error occurred while updating promotion image: ' . $e->getMessage());
        return response()->json(['error' => 'Error updating promotion image'], 500);
    }
}


    /**
     * Remove the specified resource from storage.
     */
    public function destroy($promotion_image_uuid)
{
    DB::beginTransaction();
    try {
        // Buscar la imagen de promoción por su UUID
        $promotionImage = PromotionImage::where('promotion_image_uuid', $promotion_image_uuid)->firstOrFail();

        // Eliminar la imagen del almacenamiento
        ImageHelper::deleteFileFromStorage($promotionImage->promotion_image_path);

        // Eliminar el modelo de la base de datos
        $promotionImage->delete();

        // Confirmar la transacción
        DB::commit();

        // Invalidate the cache for the deleted promotion image
        $this->invalidateUserPromotionImagesCache(Auth::id());
        $this->invalidateCache('promotion_image_' . $promotion_image_uuid);

        return response()->json(['message' => 'Promotion image deleted successfully']);
    } catch (ModelNotFoundException $e) {
        DB::rollBack();
        return response()->json(['error' => 'Promotion image not found'], 404);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('An error occurred while deleting promotion image: ' . $e->getMessage());
        return response()->json(['error' => 'Error occurred while deleting promotion image'], 500);
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

private function getPromotionImagesCacheKey($userId)
{
   
    return 'promotions_' . $userId . '_images';
}

private function invalidateUserPromotionImagesCache($userId)
{
    $cacheKey = $this->getPromotionImagesCacheKey($userId);
    $this->invalidateCache($cacheKey);
}

}
