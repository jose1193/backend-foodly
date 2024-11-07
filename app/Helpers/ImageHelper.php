<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class ImageHelper 
{
    /**
     * Store and resize an image from either a file upload or binary data
     * @param mixed $image File upload or binary string
     * @param string $storagePath Storage path in S3
     * @return string|null S3 URL of stored image
     */
    public static function storeAndResize($image, $storagePath)
    {
        try {
            Log::info('Starting image processing', ['storage_path' => $storagePath]);

            // Handle both file uploads and binary data
            if (is_string($image) && !is_file($image)) {
                // Binary data
                $interventionImage = Image::make($image);
                Log::info('Processing binary image data', [
                    'original_size' => strlen($image)
                ]);
            } else {
                // File upload
                $interventionImage = Image::make($image);
                Log::info('Processing uploaded file', [
                    'original_name' => $image->getClientOriginalName() ?? 'N/A',
                    'mime_type' => $image->getMimeType() ?? 'N/A',
                    'size' => $image->getSize() ?? 0
                ]);
            }

            // Redimensionar la imagen y guardarla temporalmente en local
            $resizedImagePath = self::resizeAndStoreTempImage($interventionImage);

            // Generar un nombre de archivo único
            $uniqueFileName = self::generateUniqueFileName();

            // Guardar la imagen redimensionada en S3 con el nombre único
            $photoPath = self::storeImageToS3($resizedImagePath, $storagePath, $uniqueFileName);

            // Eliminar la imagen temporal redimensionada
            if (file_exists($resizedImagePath)) {
                unlink($resizedImagePath);
                Log::info('Temporary file deleted', ['temp_path' => $resizedImagePath]);
            }

            Log::info('Image processing completed successfully', [
                'final_path' => $photoPath
            ]);

            return $photoPath;
        } catch (\Exception $e) {
            Log::error('Failed to process image', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Resize and store temporary image
     * @param \Intervention\Image\Image $image
     * @return string Path to temporary file
     */
    private static function resizeAndStoreTempImage($image)
    {
        try {
            // Obtener las dimensiones originales
            $originalWidth = $image->width();
            $originalHeight = $image->height();

            Log::info('Original image dimensions', [
                'width' => $originalWidth,
                'height' => $originalHeight
            ]);

            // Redimensionar si es necesario
            if ($originalWidth > 700 || $originalHeight > 700) {
                $scaleFactor = min(700 / $originalWidth, 700 / $originalHeight);
                $newWidth = (int)($originalWidth * $scaleFactor);
                $newHeight = (int)($originalHeight * $scaleFactor);
                
                $image->resize($newWidth, $newHeight);
                
                Log::info('Image resized', [
                    'new_width' => $newWidth,
                    'new_height' => $newHeight
                ]);
            }

            // Optimizar calidad
            $image->encode('jpg', 80);

            // Guardar la imagen redimensionada temporalmente
            $tempPath = sys_get_temp_dir() . '/' . uniqid() . '.jpg';
            $image->save($tempPath);

            Log::info('Temporary image saved', ['temp_path' => $tempPath]);

            return $tempPath;
        } catch (\Exception $e) {
            Log::error('Failed to resize and store temp image', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Generate a unique filename
     * @return string
     */
    private static function generateUniqueFileName()
    {
        return Str::random(40);
    }

    /**
     * Store image to S3
     * @param string $imagePath
     * @param string $storagePath
     * @param string $fileName
     * @return string S3 URL
     */
    private static function storeImageToS3($imagePath, $storagePath, $fileName)
    {
        try {
            // Leer la imagen redimensionada desde el almacenamiento temporal
            $resizedImageContent = file_get_contents($imagePath);

            // Definir la ruta de destino en S3
            $s3Path = $storagePath . '/' . $fileName . '.jpg';

            // Subir la imagen redimensionada a S3
            Storage::disk('s3')->put($s3Path, $resizedImageContent);

            Log::info('Image uploaded to S3', [
                's3_path' => $s3Path,
                'size' => strlen($resizedImageContent)
            ]);

            // Retornar la URL de la imagen en S3
            return Storage::disk('s3')->url($s3Path);
        } catch (\Exception $e) {
            Log::error('Failed to store image to S3', [
                'error' => $e->getMessage(),
                'image_path' => $imagePath,
                'storage_path' => $storagePath
            ]);
            throw $e;
        }
    }

    /**
     * Delete file from S3 storage
     * @param string $fullUrl
     * @return bool
     */
    public static function deleteFileFromStorage($fullUrl) 
    {
        try {
            // Extraer la ruta relativa de la URL completa
            $parsedUrl = parse_url($fullUrl);
            $relativePath = ltrim($parsedUrl['path'], '/');

            // Eliminar el nombre del bucket si está presente en la ruta
            $bucketName = env('AWS_BUCKET');
            $relativePath = preg_replace("/^{$bucketName}\//", '', $relativePath);

            Log::info('Attempting to delete file from S3', [
                'full_url' => $fullUrl,
                'relative_path' => $relativePath
            ]);

            if (Storage::disk('s3')->exists($relativePath)) {
                $deleted = Storage::disk('s3')->delete($relativePath);
                
                Log::info('File deletion result', [
                    'success' => $deleted,
                    'path' => $relativePath
                ]);
                
                return $deleted;
            } else {
                Log::warning("File does not exist in S3", [
                    'path' => $relativePath
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Error deleting file from S3', [
                'path' => $relativePath ?? $fullUrl,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Store and resize a profile picture
     * @param mixed $image
     * @param string $storagePath
     * @return string|null
     */
    public static function storeAndResizeProfilePhoto($image, $storagePath) 
    {
        try {
            Log::info('Starting profile photo processing');
            
            $photoPath = self::storeImage($image, $storagePath);
            self::resizeImage(storage_path('app/'.$photoPath));
            
            Log::info('Profile photo processed successfully', [
                'path' => $photoPath
            ]);
            
            return 'app/'.$photoPath;
        } catch (\Exception $e) {
            Log::error('Failed to store or resize profile photo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }
}