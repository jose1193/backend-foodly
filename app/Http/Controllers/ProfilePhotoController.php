<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\User;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Helpers\ImageHelper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\PhotoUploadRequest;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use App\Helpers\UserHelper;


class ProfilePhotoController extends Controller
{

    
    public function update(Request $request)
{
    try {
        $user = $request->user(); // Obtener el usuario autenticado
        $image = $request->file('photo'); 

        if ($image) {
            $this->validateAndSaveImage($request, $user, $image);
        } else {
            $this->deleteExistingImage($user);
        }

       
        $this->invalidateUserCache($user->id);

       
        return response()->json([
            'photo' => Cache::remember("user.{$user->id}.photo", now()->addMinutes(60), function () use ($user) {
                return $user->profile_photo_path ? asset($user->profile_photo_path) : UserHelper::generateAvatarUrl($user->name);
            }), 
        ]);
    } catch (\Exception $e) {
       
        Log::error('Error updating profile photo: ' . $e->getMessage());
        
       
        return response()->json([
            'error' => 'Failed to update profile photo',
            'message' => $e->getMessage()
        ], 500); // Código de estado HTTP para errores internos del servidor
    }
}

private function validateAndSaveImage(Request $request, $user, $image)
{
    
    $request->validate([
        'photo' => 'required|image|mimes:jpeg,png,jpg,gif|max:5048', // Validación de la imagen
    ]);

   
    if ($user->profile_photo_path) {
        ImageHelper::deleteFileFromStorage($user->profile_photo_path);
    }

    
    $photoPath = ImageHelper::storeAndResize($image, 'public/profile-photos');
    $user->update(['profile_photo_path' => $photoPath]);
}


private function invalidateUserCache($userId)
{
    // Eliminar el caché existente de la foto del usuario
    Cache::forget("user.{$userId}.photo");
}



}
