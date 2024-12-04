<?php

namespace App\Http\Controllers;

use App\Http\Controllers\BaseController as BaseController;
use Illuminate\Http\Request;
use App\Models\Promotion;
use App\Models\PromotionMedia;
use App\Http\Resources\PromotionMediaResource;
use App\Http\Requests\PromotionMediaRequest;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Helpers\ImageHelper;
use Exception;

class PromotionMediaController extends BaseController
{
    protected $cacheTime = 720;
    protected $userId;
    protected $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    protected $videoExtensions = [
        'mp4',      // Formato más común
        'mov',      // QuickTime Movie
        'webm',     // WebM
        'm4v',      // iTunes Video
        'mkv',      // Matroska Video
        'avi',      // Audio Video Interleave
        'wmv',      // Windows Media Video
        'flv',      // Flash Video
        '3gp',      // 3GPP
        'mpeg',     // MPEG
        'mpg',      // MPEG
        'ts'        // MPEG Transport Stream
    ];
    protected const IMAGE_PATH = 'public/promotion_media_images';
    protected const VIDEO_PATH = 'public/promotion_media_video';

    public function __construct()
    {
        $this->middleware('check.permission:Manager')->only(['store', 'update', 'destroy']);
        $this->middleware(function ($request, $next) {
            $this->userId = Auth::id();
            return $next($request);
        });
    }

    private function determineMediaType($file)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        
        if (in_array($extension, $this->imageExtensions)) {
            return 'Image';
        }
        
        if (in_array($extension, $this->videoExtensions)) {
            return 'Video';
        }
        
        return 'Unknown';
    }

    public function index()
    {
        try {
            $cacheKey = "promotion_media_user_{$this->userId}";
        
        $medias = $this->getCachedData($cacheKey, $this->cacheTime, function () {
            return PromotionMedia::whereHas('promotion.business', function($q) {
                $q->where('user_id', $this->userId);
            })->get();
        });

        return response()->json([
            'business_promo_reference_media' => PromotionMediaResource::collection($medias)
        ], 200);

        } catch (\Exception $e) {
        Log::error('Error fetching media: ' . $e->getMessage());
        return response()->json(['message' => 'Error fetching media'], 500);
        }
    }

    public function show($mediaUuid)
    {
        try {
        $cacheKey = "promotion_media_{$mediaUuid}";
        
        $media = $this->getCachedData($cacheKey, $this->cacheTime, function () use ($mediaUuid) {
            return PromotionMedia::where('uuid', $mediaUuid)->firstOrFail();
        });

        return response()->json(new PromotionMediaResource($media), 200);

        } catch (\Exception $e) {
        Log::error('Error fetching media: ' . $e->getMessage());
        return response()->json(['message' => 'Error fetching media'], 500);
        }
    }

    public function store(PromotionMediaRequest $request) 
    {
        DB::beginTransaction();
        try {
            $promotion = Promotion::where('uuid', $request->promotion_uuid)
                ->whereHas('business', function($q) {
                    $q->where('user_id', $this->userId);
                })->firstOrFail();
            
            $medias = [];
            foreach($request->file('business_promo_media_url') as $file) {
                $mediaType = $this->determineMediaType($file);
                $path = '';

                if ($mediaType === 'Image') {
                    // Use ImageHelper for images
                    $path = ImageHelper::storeAndResize(
                        $file,
                        self::IMAGE_PATH
                    );
                } else {
                    // Store video directly
                    $path = Storage::url($file->store(self::VIDEO_PATH));
                }

                $medias[] = PromotionMedia::create([
                    'uuid' => Uuid::uuid4()->toString(),
                    'business_promo_item_id' => $promotion->id,
                    'business_promo_media_url' => $path,
                    'media_type' => $mediaType
                ]);
            }

            $this->refreshCache("promotion_media_{$promotion->uuid}", $this->cacheTime, fn() => $medias);

            DB::commit();
            return response()->json([
                'business_promo_reference_media' => PromotionMediaResource::collection(collect($medias))
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating media: ' . $e->getMessage());
            return response()->json(['message' => 'Error creating media'], 500);
        }
    }

    public function update(PromotionMediaRequest $request, $mediaUuid)
    {
        DB::beginTransaction();
        try {
        $media = PromotionMedia::where('uuid', $mediaUuid)
            ->whereHas('promotion.business', function($q) {
                $q->where('user_id', $this->userId);
            })->firstOrFail();

        if ($request->hasFile('business_promo_media_url')) {
            // Delete old file
            if ($media->business_promo_media_url) {
                ImageHelper::deleteFileFromStorage($media->business_promo_media_url);
            }

            $file = $request->file('business_promo_media_url');
            $mediaType = $this->determineMediaType($file);
            $path = '';

            if ($mediaType === 'Image') {
                // Process image with ImageHelper
                $path = ImageHelper::storeAndResize(
                    $file,
                    self::IMAGE_PATH
                );
            } else {
                // Store video directly
                $path = Storage::url($file->store(self::VIDEO_PATH));
            }

            $media->business_promo_media_url = $path;
            $media->media_type = $mediaType;
        }

        $media->save();

        $cacheKey = "promotion_media_{$mediaUuid}";
        $this->refreshCache($cacheKey, $this->cacheTime, fn() => $media);

        DB::commit();
        return response()->json(new PromotionMediaResource($media), 200);

        } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error updating media: ' . $e->getMessage());
        return response()->json(['message' => 'Error updating media'], 500);
        }
    }

    public function destroy($mediaUuid)
    {
        DB::beginTransaction();
        try {
            $media = PromotionMedia::where('uuid', $mediaUuid)
                ->whereHas('promotion.business', function($q) {
                    $q->where('user_id', $this->userId);
                })->firstOrFail();

            // Delete the file
            if ($media->business_promo_media_url) {
                ImageHelper::deleteFileFromStorage($media->business_promo_media_url);
            }

            $media->delete();
            
            $this->invalidateCache("promotion_media_{$mediaUuid}");
            
            
            DB::commit();
            return response()->json(['message' => 'Media deleted successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting media: ' . $e->getMessage());
            return response()->json(['message' => 'Error deleting media'], 500);
        }
    }
}