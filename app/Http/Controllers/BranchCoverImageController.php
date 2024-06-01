<?php

namespace App\Http\Controllers;
use App\Http\Controllers\BaseController as BaseController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;
use App\Models\BranchCoverImage;
use App\Models\Business;
use App\Models\User;
use App\Models\BusinessBranch;
use App\Http\Requests\BusinessBranchCoverImageRequest;
use App\Http\Resources\BusinessBranchCoverImageResource;
use Ramsey\Uuid\Uuid;
use App\Http\Requests\UpdateBusinessBranchCoverImageRequest;
use Illuminate\Support\Facades\Auth;

use App\Helpers\ImageHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;


class BranchCoverImageController extends BaseController
{
    protected $cacheKey;
    
    protected $cacheTime = 720;
    protected $userId;


    public function __construct()
{
   $this->middleware('check.permission:Manager')->only(['index', 'store', 'update', 'destroy']);

    $this->middleware(function ($request, $next) {
            $this->userId = Auth::id();
            $this->cacheKey = 'user_' . $this->userId . '_branch_cover_images';
            return $next($request);
        });


}

    /**
     * Display a listing of the resource.
     */
   
public function index()
{
    try {
        // Attempt to retrieve cached data
        $groupedCoverImages = $this->getCachedData($this->cacheKey, $this->cacheTime, function () {
            return $this->getAllBranchCoverImages();
        });

        // Update cache with the grouped cover images
        $this->updateBranchCoverImagesCache($groupedCoverImages);

        // Return a JSON response with the grouped cover images
        return response()->json($groupedCoverImages);
    } catch (\Exception $e) {
        // Log and handle any exception that occurs during the process
        Log::error('Error in index function: ' . $e->getMessage());
        return response()->json(['message' => 'An error occurred during processing'], 500);
    }
}



private function getAllBranchCoverImages()
{
    // Retrieve the authenticated user with businesses and their branches with preloaded cover images
    $user = User::with('businesses.branches.coverImages')->find($this->userId);

    // Prepare an array to store the cover images grouped by branch
    $groupedCoverImages = [];

    // Iterate over the businesses and their branches to group the cover images
    foreach ($user->businesses as $business) {
        foreach ($business->branches as $branch) {
            // Use the branch name as the key to group the cover images
            $branchName = $branch->branch_name; // Ensure 'branch_name' is the correct attribute
            $groupedCoverImages[$branchName] = BusinessBranchCoverImageResource::collection($branch->coverImages);
        }
    }

    return $groupedCoverImages;
}


    private function updateBranchCoverImagesCache($groupedCoverImages)
    {
    $this->refreshCache($this->cacheKey, $this->cacheTime, function () use ($groupedCoverImages) {
        return $groupedCoverImages;
    });
    }

    /**
     * Store a newly created resource in storage.
     */
    
public function store(BusinessBranchCoverImageRequest $request)
{
    DB::beginTransaction();  // Start the transaction
    try {
        $userId = Auth::id();
        $validatedData = $request->validated();

        // Retrieve the user's business IDs and check if the branch ID is valid
        $userBusinesses = Business::where('user_id', $userId)->pluck('id');
        Log::info('User Businesses:', $userBusinesses->toArray());
        Log::info('Branch ID from request:', [$validatedData['branch_id']]);

        $branch = BusinessBranch::find($validatedData['branch_id']);
        if (!$branch || !$userBusinesses->contains($branch->business_id)) {
            return response()->json(['error' => 'Invalid branch ID.'], 400);
        }

        // Ensure branch_image_path is an array
        $branchImages = $validatedData['branch_image_path'];
        if (!is_array($branchImages)) {
            $branchImages = [$branchImages];  // Convert to array if not already
        }

        $branchCoverImages = collect($branchImages)->map(function ($image) use ($validatedData) {
            if ($image->isValid()) { // Ensure the file is valid
                $storedImagePath = ImageHelper::storeAndResize($image, 'public/branch_photos');

                return BranchCoverImage::create([
                    'branch_image_path' => $storedImagePath,
                    'branch_id' => $validatedData['branch_id'],
                    'branch_image_uuid' => Uuid::uuid4()->toString(),
                ]);
            } else {
                throw new \Exception("Invalid image file.");
            }
        })->map(function ($branchCoverImage) {
            return new BusinessBranchCoverImageResource($branchCoverImage);
        });

        // Update the cache for each new image
        $branchCoverImages->each(function ($branchCoverImageResource) {
            $this->refreshCache('branch_cover_image_' . $branchCoverImageResource->branch_image_uuid, $this->cacheTime, function () use ($branchCoverImageResource) {
                return $branchCoverImageResource;
            });
        });

        // Update the cache with all branch cover images
        $this->updateBranchCoverImagesCache($this->getAllBranchCoverImages());

        DB::commit();  // Commit the transaction

        return response()->json($branchCoverImages, 200);
    } catch (\Exception $e) {
        DB::rollBack();  // Roll back the transaction in case of failure
        Log::error('An error occurred while storing branch cover images: ' . $e->getMessage());
        return response()->json(['error' => 'Error storing branch cover images'], 500);
    }
}







public function updateImage(UpdateBusinessBranchCoverImageRequest $request, $uuid)
{
    DB::beginTransaction(); // Start the transaction
    try {
        $branchCoverImage = BranchCoverImage::where('branch_image_uuid', $uuid)->firstOrFail();

        if ($request->hasFile('branch_image_path')) {
            $storedImagePath = ImageHelper::storeAndResize(
                $request->file('branch_image_path'), 
                'public/branch_photos'
            );

            // Delete the old image if it exists
            if ($branchCoverImage->branch_image_path) {
                ImageHelper::deleteFileFromStorage($branchCoverImage->branch_image_path);
            }

            // Update the image path
            $branchCoverImage->update([
                'branch_image_path' => $storedImagePath
            ]);
        }

        // Update the cache for the specific image
        $this->refreshCache('branch_cover_image_' . $uuid, $this->cacheTime, function () use ($branchCoverImage) {
            return new BusinessBranchCoverImageResource($branchCoverImage);
        });

        // Refresh the cache for all branch cover images
        $groupedCoverImages = $this->getAllBranchCoverImages();
        $this->updateBranchCoverImagesCache($groupedCoverImages);

        DB::commit(); // Commit the changes if everything is correct

        return response()->json(new BusinessBranchCoverImageResource($branchCoverImage));
    } catch (\Exception $e) {
        DB::rollBack(); // Roll back all changes in case of error
        Log::error('An error occurred while updating branch cover images: ' . $e->getMessage());
        return response()->json(['error' => 'Error updating business cover image'], 500);
    }
}



    /**
     * Display the specified resource.
     */
   public function show($uuid)
{
    // Validate the UUID format
    if (!Uuid::isValid($uuid)) {
        return response()->json(['error' => 'Invalid UUID format'], 400);
    }

    try {
       
        $cacheKey = 'branch_cover_image_' . $uuid;

        // Attempt to retrieve cached data
        $branchCoverImage = $this->getCachedData($cacheKey, $this->cacheTime, function () use ($uuid) {
            // Find the branch cover image by its branch_image_uuid
            return BranchCoverImage::where('branch_image_uuid', $uuid)->firstOrFail();
        });

        // Return the branch cover image
        return response()->json(new BusinessBranchCoverImageResource($branchCoverImage));
        
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // Handle the exception and return an error response if the image is not found
        return response()->json(['message' => 'Branch cover image not found'], 404);
    } catch (\Exception $e) {
        // Handle any other exceptions and log the error
        Log::error('Failed to retrieve branch cover image: ' . $e->getMessage());
        return response()->json(['message' => 'Failed to retrieve branch cover image'], 500);
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
    if (!Uuid::isValid($uuid)) {
        return response()->json(['error' => 'Invalid UUID format'], 400);
    }

    try {
        DB::beginTransaction();

        $branchCoverImage = BranchCoverImage::where('branch_image_uuid', $uuid)->firstOrFail();
        ImageHelper::deleteFileFromStorage($branchCoverImage->branch_image_path);
        $branchCoverImage->delete();

        DB::commit();

        // Invalidate the cache for the deleted image
        $this->invalidateCache('branch_cover_image_' . $uuid);

        // Refresh the cache for all branch cover images
        $groupedCoverImages = $this->getAllBranchCoverImages();
        $this->updateBranchCoverImagesCache($groupedCoverImages);

        return response()->json(['message' => 'Branch cover image deleted successfully'], 200);
    } catch (ModelNotFoundException $e) {
        DB::rollBack();
        return response()->json(['message' => 'Failed to delete branch cover image'], 404);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error deleting branch cover image: ' . $e->getMessage());
        return response()->json(['error' => 'Error deleting branch cover image: ' . $e->getMessage()], 500);
    }
}







}
