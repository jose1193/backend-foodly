<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;

class ImageHelper
{
    public static function storeAndResize($image, $storagePath)
    {
        // Redimensionar la imagen y guardarla temporalmente en local
        $resizedImagePath = self::resizeAndStoreTempImage($image);

        // Generar un nombre de archivo único
        $uniqueFileName = self::generateUniqueFileName();

        // Guardar la imagen redimensionada en S3 con el nombre único
        $photoPath = self::storeImageToS3($resizedImagePath, $storagePath, $uniqueFileName);

        // Eliminar la imagen temporal redimensionada
        unlink($resizedImagePath);

        return $photoPath;
    }

    private static function resizeAndStoreTempImage($image)
    {
        // Crear una imagen de Intervention a partir del archivo
        $image = Image::make($image);

        // Obtener las dimensiones originales
        $originalWidth = $image->width();
        $originalHeight = $image->height();

        // Redimensionar si es necesario
        if ($originalWidth > 700 || $originalHeight > 700) {
            $scaleFactor = min(700 / $originalWidth, 700 / $originalHeight);
            $newWidth = $originalWidth * $scaleFactor;
            $newHeight = $originalHeight * $scaleFactor;
            $image->resize($newWidth, $newHeight);
        }

        // Guardar la imagen redimensionada temporalmente
        $tempPath = sys_get_temp_dir() . '/' . uniqid() . '.jpg';
        $image->save($tempPath);

        return $tempPath;
    }

    private static function generateUniqueFileName()
    {
        // Generar una cadena única de 40 caracteres
        return Str::random(40);
    }

    private static function storeImageToS3($imagePath, $storagePath, $fileName)
    {
        // Leer la imagen redimensionada desde el almacenamiento temporal
        $resizedImageContent = file_get_contents($imagePath);

        // Definir la ruta de destino en S3
        $s3Path = $storagePath . '/' . $fileName . '.jpg';

        // Subir la imagen redimensionada a S3
        Storage::disk('s3')->put($s3Path, $resizedImageContent);

        // Retornar la URL de la imagen en S3
        return Storage::disk('s3')->url($s3Path);
    }


    //DELETE IMAGE FROM STORAGE
public static function deleteFileFromStorage($filePath)
{
    // Obtener la URL base desde la variable de entorno
    $baseUrl = env('AWS_URL');

    // Asegurarse de que la URL base termina con una barra diagonal para la comparación
    if (substr($baseUrl, -1) !== '/') {
        $baseUrl .= '/';
    }

    // Extraer el path relativo de la URL completa de S3
    $path = str_replace($baseUrl, '', $filePath);

    // Comprobar si el archivo existe en S3 y eliminarlo
    if (Storage::disk('s3')->exists($path)) {
        Storage::disk('s3')->delete($path);
    }
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

}
