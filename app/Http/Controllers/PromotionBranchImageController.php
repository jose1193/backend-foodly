<?php

namespace App\Http\Controllers;
use App\Http\Controllers\BaseController as BaseController;
use Illuminate\Http\Request;

use App\Models\PromotionBranchImage;
use App\Models\PromotionBranch;
use App\Models\User;
use App\Http\Requests\PromotionBranchImageRequest;
use App\Http\Resources\PromotionBranchImageResource;
use Ramsey\Uuid\Uuid;
use App\Http\Requests\UpdatePromotionBranchImageRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Helpers\ImageHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;



class PromotionBranchImageController extends BaseController
{
    protected $cacheKey;
    
    protected $cacheTime = 720;
    protected $userId;

    public function __construct()
{
   $this->middleware('check.permission:Manager')->only(['index','create', 'store', 'edit', 'update', 'destroy']);
    
   $this->middleware(function ($request, $next) {
            $this->userId = auth()->id();
            $this->cacheKey = 'user_'. $this->userId . '_branch_promotions_images';
            return $next($request);
        });

}


    /**
     * Display a listing of the resource.
     */
   public function index()
{
    try {
        // Obtener el ID del usuario autenticado
        $userId =  $this->userId;

        // Intentar obtener los datos en caché
        $groupedPromotionImages = $this->getCachedData($this->cacheKey, $this->cacheTime, function() use ($userId) {
            return $this->getGroupedBranchPromotionImages($userId);
        });

        // Devolver todas las imágenes de promoción agrupadas por nombre de sucursal como respuesta JSON
        return response()->json(['grouped_promotion_images' => $groupedPromotionImages], 200);
    } catch (\Exception $e) {
        // Devolver un mensaje de error detallado en caso de excepción
        Log::error('Error fetching promotion images: ' . $e->getMessage());
        return response()->json(['message' => 'Error fetching promotion images'], 500);
    }
}

protected function getGroupedBranchPromotionImages($userId)
{
    
    $user = User::with('businesses.branches.promotionsbranches.promotionBranchesImages')->findOrFail($userId);

   
    $groupedPromotionImages = [];

   
    foreach ($user->businesses as $business) {
      
        foreach ($business->branches as $branch) {
            $branchName = $branch->branch_name;

            $promotionImages = [];

            // Iterar sobre cada promoción y obtener sus imágenes asociadas
            foreach ($branch->promotionsbranches as $promotion) {
                // Obtener las imágenes de promoción de la promoción
                $images = $promotion->promotionBranchesImages;

                // Agregar las imágenes de promoción al array asociado a la promoción
                $promotionImages[$promotion->id] = PromotionBranchImageResource::collection($images);
            }

            // Agregar las promociones y sus imágenes asociadas al array asociado al nombre de la sucursal
            $groupedPromotionImages[$branchName] = $promotionImages;
        }
    }

    return $groupedPromotionImages;
}




    /**
     * Store a newly created resource in storage.
     */
    public function store(PromotionBranchImageRequest $request)
{
    try {
        DB::beginTransaction();

        $validatedData = $request->validated();

        $promotionBranchImages = [];

        foreach ($validatedData['promotion_branch_image_path'] as $image) {
            $storedImagePath = ImageHelper::storeAndResize($image, 'public/promotion_branches_photos');

            $promotionBranchImage = PromotionBranchImage::create([
                'promotion_branch_image_path' => $storedImagePath,
                'promotion_branch_id' => $validatedData['promotion_branch_id'],
                'promotion_branch_image_uuid' => Uuid::uuid4()->toString(),
            ]);

            $promotionBranchImages[] = new PromotionBranchImageResource($promotionBranchImage);
        }

        foreach ($promotionBranchImages as $promotionBranchImageResource) {
            $this->refreshCache(
                'branch_promotion_image_' . $promotionBranchImageResource->promotion_branch_image_uuid,
                $this->cacheTime,
                function () use ($promotionBranchImageResource) {
                    return $promotionBranchImageResource;
                }
            );
        }

        $this->updateBranchPromotionImagesCache($this->getGroupedBranchPromotionImages($this->userId));

        DB::commit();

        return response()->json($promotionBranchImages, 200);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error storing branch promotion images: ' . $e->getMessage());
        return response()->json(['error' => 'Error storing branch promotion images'], 500);
    }
}


    /**
     * Display the specified resource.
     */
    public function show($uuid)
{
    try {

        $promotionBranchImageResource = Cache::remember('branch_promotion_image_' . $uuid, $this->cacheTime, function() use ($uuid) {
            // Encontrar la imagen de promoción por su uuid
            $promotionBranchImage = PromotionBranchImage::where('promotion_branch_image_uuid', $uuid)->first();

            // Verificar si se encontró la imagen de promoción
            if (!$promotionBranchImage) {
                return null;
            }

            // Crear un recurso para la imagen de promoción
            return new PromotionBranchImageResource($promotionBranchImage);
        });

        // Verificar si se encontró la imagen de promoción
        if (!$promotionBranchImageResource) {
            return response()->json(['message' => 'Promotion Branch image not found'], 404);
        }

        // Devolver el recurso de imagen de promoción
        return response()->json($promotionBranchImageResource, 200);
    } catch (\Exception $e) {
        // Manejar errores de manera más detallada
        Log::error('Error showing branch promotion image: ' . $e->getMessage());
        return response()->json(['error' => 'Error showing branch promotion image'], 500);
    }
}



public function updateImage(UpdatePromotionBranchImageRequest $request, $uuid)
{
    try {
       
        $promotionImage = PromotionBranchImage::where('promotion_branch_image_uuid', $uuid)->firstOrFail();

        if ($request->hasFile('promotion_branch_image_path')) {
         
            $storedImagePath = ImageHelper::storeAndResize($request->file('promotion_branch_image_path'), 'public/promotion_branches_photos');

            ImageHelper::deleteFileFromStorage($promotionImage->promotion_branch_image_path);
           

            $promotionImage->promotion_branch_image_path = $storedImagePath;
            $promotionImage->save();

            // Cache
            $cacheKey = "branch_promotion_image_{$uuid}";
            $this->refreshCache($cacheKey, $this->cacheTime, function () use ($promotionImage) {
            return $promotionImage;
            });
            $this->updateBranchPromotionImagesCache($this->getGroupedBranchPromotionImages($this->userId));
         }

        return response()->json(new PromotionBranchImageResource($promotionImage));
    } catch (\Exception $e) {
        Log::error('An error occurred while updating promotion image: ' . $e->getMessage());
        return response()->json(['error' => 'Error updating promotion image'], 500);
    }
}





    /**
     * Update the specified resource in storage.
     */

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($uuid)
{
    try {
        
        $promotionBranchImage = PromotionBranchImage::where('promotion_branch_image_uuid', $uuid)->first();

       
        if (!$promotionBranchImage) {
            return response()->json(['message' => 'Promotion Branch image not found'], 404);
        }

       
        ImageHelper::deleteFileFromStorage($promotionBranchImage->promotion_branch_image_path);
       
        
        $promotionBranchImage->delete();

        $this->invalidateUserBranchPromotionImagesCache(Auth::id());

        // Invalidar la caché para la imagen de promoción específica
        $cacheKey = "branch_promotion_image_{$uuid}";
        $this->invalidateCache($cacheKey);
         $this->updateBranchPromotionImagesCache($this->getGroupedBranchPromotionImages($this->userId));

        return response()->json(['message' => 'Promotion branch image deleted successfully']);
    } catch (\Exception $e) {
        Log::error('An error occurred while deleting branch promotion image: ' . $e->getMessage());
        return response()->json(['error' => 'Error occurred while deleting branch promotion image'], 500);
    }
}




private function updateBranchPromotionImagesCache($groupedPromotionImages)
{
    $this->refreshCache($this->cacheKey, $this->cacheTime, function () use ($groupedPromotionImages) {
        return $groupedPromotionImages;
    });
}




}
