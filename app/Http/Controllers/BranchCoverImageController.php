<?php

namespace App\Http\Controllers;
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
use Illuminate\Routing\Controller as BaseController;

class BranchCoverImageController extends BaseController
{
    // PERMISSIONS USERS
    public function __construct()
{
   $this->middleware('check.permission:Manager')->only(['index', 'store', 'update', 'destroy']);

}

    /**
     * Display a listing of the resource.
     */
   
public function index()
{
    try {
        $userId = Auth::id();
        $cacheKey = 'user_' . $userId . '_branch_cover_images';

        // Attempt to retrieve cached data
        $groupedCoverImages = $this->getCachedData($cacheKey, 60, function () use ($userId) {
            // Obtener el usuario autenticado con negocios y sus sucursales con imágenes de portada cargadas de antemano
            $user = User::with('businesses.branches.coverImages')->find($userId);

            // Preparar un array para almacenar las imágenes de portada agrupadas por sucursal
            $groupedCoverImages = [];

            // Iterar sobre los negocios y sus sucursales para agrupar las imágenes de portada
            foreach ($user->businesses as $business) {
                foreach ($business->branches as $branch) {
                    // Usar el nombre de la sucursal como clave para agrupar las imágenes de portada
                    $branchName = $branch->branch_name; // Asegúrate de que 'branch_name' es el atributo correcto
                    $groupedCoverImages[$branchName] = BusinessBranchCoverImageResource::collection($branch->coverImages);
                }
            }

            return $groupedCoverImages;
        });

        // Devolver una respuesta JSON con las imágenes de portada agrupadas por sucursal
        return response()->json($groupedCoverImages);
    } catch (\Exception $e) {
        // Registrar y manejar cualquier excepción que ocurra durante el proceso
        Log::error('Error in index function: ' . $e->getMessage());
        return response()->json(['message' => 'An error occurred during processing'], 500);
    }
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

        DB::commit();  // Commit the transaction

        // Invalidate the cache for the user's branch cover images
        $this->invalidateUserBranchCoverImageCache($userId);

        return response()->json($branchCoverImages, 200);
    } catch (\Exception $e) {
        DB::rollBack();  // Roll back the transaction in case of failure
        Log::error('An error occurred while storing branch cover images: ' . $e->getMessage());
        return response()->json(['error' => 'Error storing branch cover images'], 500);
    }
}






public function updateImage(UpdateBusinessBranchCoverImageRequest $request, $uuid)
{
    DB::beginTransaction(); // Iniciar transacción
    try {
        $branchCoverImage = BranchCoverImage::where('branch_image_uuid', $uuid)->firstOrFail();

        if ($request->hasFile('branch_image_path')) {
            $storedImagePath = ImageHelper::storeAndResize(
                $request->file('branch_image_path'), 
                'public/branch_photos'
            );

              if ($branchCoverImage->branch_image_path) {
                ImageHelper::deleteFileFromStorage($branchCoverImage->branch_image_path);
                }

            $branchCoverImage->update([
                'branch_image_path' => $storedImagePath
            ]);
        }

        DB::commit(); // Confirmar cambios si todo es correcto

        // Invalidate the cache for the updated image
        $this->invalidateCache('branch_cover_image_' . $uuid);

        return response()->json(new BusinessBranchCoverImageResource($branchCoverImage));
    } catch (\Exception $e) {
        DB::rollBack(); // Revertir todos los cambios en caso de error
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
        $branchCoverImage = $this->getCachedData($cacheKey, 60, function () use ($uuid) {
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

private function invalidateUserBranchCoverImageCache($userId)
{
    $cacheKey = 'user_' . $userId . '_branch_cover_images';
    $this->invalidateCache($cacheKey);
}

}
