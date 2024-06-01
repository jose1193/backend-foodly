<?php

namespace App\Http\Controllers;
use App\Http\Controllers\BaseController as BaseController;
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


class PromotionCoverImageController extends BaseController
{
    protected $cacheKey;
    protected $cacheTime = 720;
    protected $userId;

    // PERMISSIONS USERS
    public function __construct()
{
   $this->middleware('check.permission:Manager')->only(['index','store', 'edit', 'update', 'destroy']);

   $this->middleware(function ($request, $next) {
            $this->userId = Auth::id();
            $this->cacheKey = 'user_'. $this->userId . '_business_promotions_images';
            return $next($request);
        });
}


    /**
     * Display a listing of the resource.
     */
   public function index()
{
    try {
        $userId = $this->userId;
        $groupedPromotionImages = $this->getCachedData($this->cacheKey, $this->cacheTime, function() use ($userId) {
            return $this->getGroupedPromotionImages($userId);
        });

        return response()->json(['grouped_promotion_images' => $groupedPromotionImages], 200);
    } catch (\Exception $e) {
        Log::error('An error occurred while fetching promotion images: ' . $e->getMessage());
        return response()->json(['message' => 'Error fetching promotion images'], 500);
    }
}

private function getGroupedPromotionImages($userId)
{
    $businesses = User::findOrFail($userId)->businesses()->with('promotions.promotionImages')->get();

    $groupedPromotionImages = [];
    foreach ($businesses as $business) {
        foreach ($business->promotions as $promotion) {
            $promotionTitle = $promotion->promotion_title;
            $promotionImages = $promotion->promotionImages;

            $groupedPromotionImages[$promotionTitle] = PromotionImageResource::collection($promotionImages);
        }
    }

    return $groupedPromotionImages;
    }





    /**
     * Store a newly created resource in storage.
     */
    public function store(PromotionCoverImageRequest $request)
{
    try {
       
        DB::beginTransaction();

        
        $validatedData = $request->validated();
        $promotionImages = [];

        
        foreach ($validatedData['promotion_image_path'] as $image) {
           
            $storedImagePath = ImageHelper::storeAndResize($image, 'public/promotion_photos');

           
            $promotionImage = PromotionImage::create([
                'promotion_image_path' => $storedImagePath,
                'promotion_id' => $validatedData['promotion_id'],
                'promotion_image_uuid' => Uuid::uuid4()->toString(),
            ]);

           
            $promotionImages[] = new PromotionImageResource($promotionImage);
        }

        // Cache the promotion images
        foreach ($promotionImages as $promotionImageResource) {
            $this->refreshCache(
                'business_promotion_image_' . $promotionImageResource->promotion_image_uuid,
                $this->cacheTime,
                function () use ($promotionImageResource) {
                    return $promotionImageResource;
                }
            );
        }

        // Update the promotion images cache
        $groupedPromotionImages = $this->getGroupedPromotionImages($this->userId);
        $this->updatePromotionImagesCache($groupedPromotionImages);


        DB::commit();

       
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
        $cacheKey ='business_promotion_image_' . $uuid;
       
        // Attempt to retrieve the promotion image from the cache
        $promotionImageResource = $this->getCachedData($cacheKey . $uuid,  $this->cacheTime, function() use ($uuid) {
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
        $cacheKey = "business_promotion_image_{$promotion_image_uuid}";
        
        $promotionImage = $this->getCachedData($cacheKey, $this->cacheTime, function () use ($promotion_image_uuid) {
            return PromotionImage::where('promotion_image_uuid', $promotion_image_uuid)->firstOrFail();
        });

      

        if ($request->hasFile('promotion_image_path')) {
            // Store and resize the new image
            $storedImagePath = ImageHelper::storeAndResize($request->file('promotion_image_path'), 'public/promotion_photos');

           
            ImageHelper::deleteFileFromStorage($promotionImage->promotion_image_path);

           
            $promotionImage->promotion_image_path = $storedImagePath;
            $promotionImage->save();
        }
        
          // Invalidate the cache for the updated promotion image
        $this->refreshCache($cacheKey, $this->cacheTime, function () use ($promotionImage) {
            return $promotionImage;
        });

        // Update the grouped promotion images cache
        $this->updateGroupedPromotionImagesCache();


        // Commit the transaction
        DB::commit();

      
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
        
        // Find the promotion image by its UUID
        $cacheKey = "business_promotion_image_{$promotion_image_uuid}";
       
        $promotionImage = $this->getCachedData($cacheKey, $this->cacheTime, function () use ($promotion_image_uuid) {
            return PromotionImage::where('promotion_image_uuid', $promotion_image_uuid)->firstOrFail();
        });

       
        ImageHelper::deleteFileFromStorage($promotionImage->promotion_image_path);

       
        $promotionImage->delete();

        
        DB::commit();

        // Invalidate the cache for the updated promotion image
        $this->invalidateCache($cacheKey);

        // Update the grouped promotion images cache
        $this->updateGroupedPromotionImagesCache();

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



private function updateGroupedPromotionImagesCache()
{

    // Get the grouped promotion images
    $groupedPromotionImages = $this->getGroupedPromotionImages($this->userId);
    
    $this->updatePromotionImagesCache($groupedPromotionImages);
   
}
        

private function updatePromotionImagesCache($groupedPromotionImages)
{
    $this->refreshCache($this->cacheKey, $this->cacheTime, function () use ($groupedPromotionImages) {
        return $groupedPromotionImages;
    });
}



}
