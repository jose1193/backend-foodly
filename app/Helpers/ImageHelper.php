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
public static function deleteFileFromStorage($fullUrl)
{
    // Extraer la ruta relativa de la URL completa
    $parsedUrl = parse_url($fullUrl);
    $relativePath = ltrim($parsedUrl['path'], '/');

    // Eliminar el nombre del bucket si está presente en la ruta
    $bucketName = env('AWS_BUCKET');
    $relativePath = preg_replace("/^{$bucketName}\//", '', $relativePath);

    try {
        if (Storage::disk('s3')->exists($relativePath)) {
            $deleted = Storage::disk('s3')->delete($relativePath);
           
            return $deleted;
        } else {
            \Log::warning("El archivo no existe en S3: {$relativePath}");
            return false;
        }
    } catch (\Exception $e) {
        \Log::error("Error al eliminar archivo de S3: {$relativePath}. Error: " . $e->getMessage());
        return false;
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

public static function storeAndResizeBinaryImage($binaryData, $storagePath)
{
    try {
        // Debug log para ver qué tipo de datos estamos recibiendo
        \Log::info("Received data type: " . gettype($binaryData));
        \Log::info("Data length: " . strlen($binaryData));

        // Si es base64, decodificar
        if (is_string($binaryData) && preg_match('/^data:image\/(\w+);base64,/', $binaryData, $matches)) {
            \Log::info("Detected base64 image");
            $binaryData = substr($binaryData, strpos($binaryData, ',') + 1);
            $binaryData = base64_decode($binaryData);
        }

        if (empty($binaryData)) {
            throw new \Exception("Image data is empty");
        }

        try {
            // Intentar crear imagen desde binary string
            $image = Image::make($binaryData);
        } catch (\Exception $e) {
            \Log::error("Failed to create image from data: " . $e->getMessage());
            
            // Si falla, intentar guardar temporalmente y cargar desde archivo
            $tempFile = tempnam(sys_get_temp_dir(), 'img_');
            file_put_contents($tempFile, $binaryData);
            
            try {
                $image = Image::make($tempFile);
                unlink($tempFile); // Limpiamos el archivo temporal
            } catch (\Exception $e2) {
                unlink($tempFile); // Limpiamos el archivo temporal
                throw new \Exception("Could not read image data: " . $e2->getMessage());
            }
        }

        // Verificar que tenemos una imagen válida
        if (!$image->width() || !$image->height()) {
            throw new \Exception("Invalid image dimensions");
        }

        \Log::info("Successfully created image. Dimensions: {$image->width()}x{$image->height()}");

        // Redimensionar si es necesario
        if ($image->width() > 700 || $image->height() > 700) {
            $scaleFactor = min(700 / $image->width(), 700 / $image->height());
            $newWidth = $image->width() * $scaleFactor;
            $newHeight = $image->height() * $scaleFactor;
            $image->resize($newWidth, $newHeight);
            \Log::info("Image resized to: {$newWidth}x{$newHeight}");
        }

        // Generar nombre único
        $fileName = self::generateUniqueFileName();
        $s3Path = $storagePath . '/' . $fileName . '.jpg';

        // Convertir la imagen procesada a string con calidad específica
        $imageStream = $image->encode('jpg', 90);

        // Subir a S3
        $success = Storage::disk('s3')->put($s3Path, $imageStream);
        
        if (!$success) {
            throw new \Exception("Failed to upload image to S3");
        }

        \Log::info("Successfully uploaded image to S3: {$s3Path}");

        // Retornar la URL de S3
        return Storage::disk('s3')->url($s3Path);

    } catch (\Exception $e) {
        \Log::error("Error processing binary image: " . $e->getMessage());
        \Log::error("Stack trace: " . $e->getTraceAsString());
        throw new \Exception("Error processing image: " . $e->getMessage());
    }
}


}
