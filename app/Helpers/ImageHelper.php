<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class ImageHelper
{
    public static function storeAndResize($image, $storagePath)
    {
        $photoPath = self::storeImage($image, $storagePath);
        self::resizeImage(storage_path('app/'.$photoPath));
        return 'storage/app/'.$photoPath;
    }


    // Stores and resizes a profile picture, returns the relative path of the stored file, handles exceptions.
    public static function storeAndResizeProfilePhoto($image, $storagePath)
{
    try {
        $photoPath = self::storeImage($image, $storagePath);
        self::resizeImage(storage_path('app/'.$photoPath));
        return 'app/'.$photoPath;
    } catch (\Exception $e) {
        Log::error('Failed to store or resize image: ' . $e->getMessage());
        return null;  // Retorna null o maneja el error como sea apropiado.
    }
}


    private static function storeImage($image, $storagePath)
    {
        // Guardar la imagen
        $photoPath = $image->store($storagePath);
        return $photoPath;
    }

    private static function resizeImage($imagePath)
    {
        // Redimensionar la imagen si es necesario
        $image = Image::make($imagePath);
        $originalWidth = $image->width();
        $originalHeight = $image->height();

        if ($originalWidth > 700 || $originalHeight > 700) {
            $scaleFactor = min(700 / $originalWidth, 700 / $originalHeight);
            $newWidth = $originalWidth * $scaleFactor;
            $newHeight = $originalHeight * $scaleFactor;
            $image->resize($newWidth, $newHeight);
            $image->save();
        }
    }



    //DELETE IMAGE FROM STORAGE
public static function deleteFileFromStorage($filePath)
    {
        $path = str_replace('storage/app/public/', '', $filePath);
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }


}
