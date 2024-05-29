<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Service;
use App\Http\Requests\ServiceRequest;
use App\Http\Resources\ServiceResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;
use Ramsey\Uuid\Uuid;
use App\Http\Requests\UpdateCategoryImageRequest;
use Illuminate\Support\Facades\Log;
use App\Helpers\ImageHelper;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Requests\UpdateServiceImageRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;


use Illuminate\Routing\Controller as BaseController;


class ServiceController extends BaseController
{

     // PERMISSIONS USERS
    public function __construct()
{
   $this->middleware('check.permission:Super Admin')->only(['store', 'update', 'destroy','updateImage','show']);

}


    /**
     * Display a listing of the resource.
     */
   public function index()
{
    $cacheKey = 'services'; // Define una clave de caché única

    // Utiliza Cache::remember para almacenar en caché los resultados
    $categories = Cache::remember($cacheKey, now()->addMinutes(720), function () {
        return Service::orderBy('id', 'desc')->get();
    });

    return response()->json(['services' => ServiceResource::collection($categories)]);
}



    /**
     * Store a newly created resource in storage.
     */
    public function store(ServiceRequest $request)
{
    $validatedData = $this->prepareData($request);

    return DB::transaction(function () use ($request, $validatedData) {
        try {
            $service = $this->createData($validatedData);
            $this->handleServiceImage($request, $service);

             // Actualizar caché
        $this->updateServicesCache();

            return $this->successfulResponse($service);
        } catch (\Throwable $e) {
            Log::error('An error occurred while creating service: ' . $e->getMessage());
            return $this->errorResponse();
        }
    });
}

private function updateServicesCache()
{
    $cacheKey = 'services';
    $cacheTime = config('cache.times.services', 43200); // Asegurarse de usar el mismo tiempo de caché

    Cache::forget($cacheKey); // Eliminar el caché existente
    Cache::remember($cacheKey, now()->addSeconds($cacheTime), function () {
        return Service::orderBy('id', 'desc')->get();
    });
}


private function prepareData($request)
{
    return array_merge($request->validated(), [
        'user_id' => Auth::id(),
        'service_uuid' => Uuid::uuid4()->toString()
    ]);
}

private function createData($data)
{
    return Service::create($data);
}

private function handleServiceImage($request, $service)
{
    if ($request->hasFile('service_image_path')) {
        $imagePath = ImageHelper::storeAndResize($request->file('service_image_path'), 'public/services_images');
        $service->service_image_path = $imagePath;
        $service->save();
    }
}

private function successfulResponse($service)
{
    return response()->json(new ServiceResource($service), 200);
}

private function errorResponse()
{
    return response()->json(['error' => 'An error occurred while creating service'], 500);
}

    /**
     * Display the specified resource.
     */

     

     
public function updateImage(UpdateServiceImageRequest $request, $uuid)
{
    return DB::transaction(function () use ($request, $uuid) {
        try {
            $service = Service::where('service_uuid', $uuid)->firstOrFail();

            // Guardar la imagen si está presente
            if ($request->hasFile('service_image_path')) {
            // Obtener el archivo de imagen
            $image = $request->file('service_image_path');

            // Eliminar la imagen anterior si existe
            if ($service->service_image_path) {
                $this->deleteImage($service->service_image_path);
            }

            // Guardar la nueva imagen y obtener la ruta
            $photoPath = ImageHelper::storeAndResize($image, 'public/services_images');

            // Actualizar la ruta de la imagen en el modelo Business
            $service->service_image_path = $photoPath;
            $service->save();

             
            $this->updateServiceCache($service);
        }

            // Devolver el recurso actualizado
            return response()->json(new ServiceResource($service), 200);
        } catch (\Throwable $e) {
            // Manejar el error y registrar el mensaje de error si es necesario
            Log::error('Error updating service image: ' . $e->getMessage());
            return response()->json(['error' => 'Error updating service image'], 500);
        }
    });
}

private function updateServiceCache($service)
{
    $cacheKey = 'service_' . $service->service_uuid;
    $cacheTime = config('cache.times.services', 43200); // Tiempo de caché en segundos
    $cacheMinutes = $cacheTime / 60; // Convertir segundos a minutos

    Cache::forget($cacheKey); // Eliminar el caché existente
    Cache::put($cacheKey, $service->fresh(), now()->addMinutes($cacheMinutes));
}



private function deleteImage($imagePath)
{
    // Eliminar la imagen
    $pathWithoutAppPublic = str_replace('storage/app/public/', '', $imagePath);
    Storage::disk('public')->delete($pathWithoutAppPublic);
}



    public function show($uuid)
{
    $cacheKey = 'service_' . $uuid; // Clave de caché única para cada servicio basado en su UUID
    $cacheTime = config('cache.times.service', 43200); // Tiempo de caché en segundos
    $cacheMinutes = $cacheTime / 60; // Convertir segundos a minutos

    try {
        // Usar Cache::remember para almacenar y recuperar el servicio en caché
        $service = Cache::remember($cacheKey, now()->addMinutes($cacheMinutes), function () use ($uuid) {
            return Service::where('service_uuid', $uuid)->firstOrFail();
        });

        // Devolver una respuesta JSON con el servicio encontrado
        return response()->json(new ServiceResource($service), 200);
    } catch (ModelNotFoundException $e) {
        // Manejar el caso en que el servicio no se encuentre
        return response()->json(['message' => 'Service not found'], 404);
    }
}


    /**
     * Update the specified resource in storage.
     */
    public function update(ServiceRequest $request, $uuid)
{
    return DB::transaction(function () use ($request, $uuid) {
        try {
            // Encontrar el servicio por su UUID
            $service = Service::where('service_uuid', $uuid)->firstOrFail();

            // Verificar si el service_name ya está registrado en otro servicio
            $existingService = Service::where('service_name', $request->service_name)
                                      ->where('service_uuid', '!=', $uuid)
                                      ->first();

            if ($existingService) {
                return response()->json(['message' => 'Service name already taken'], 409);
            }

            // Actualizar el servicio con los datos validados de la solicitud
            $service->update($request->validated());

            // Eliminar la caché de la lista de servicios para asegurar consistencia
            Cache::forget('services');

            // Actualizar la caché del servicio específico
            $this->updateServiceCache($service);

            // Devolver una respuesta JSON con el servicio actualizado
            return response()->json(new ServiceResource($service), 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Service not found'], 404);
        } catch (\Exception $e) {
            // Manejar cualquier excepción y devolver una respuesta de error
            Log::error('Error updating service: ' . $e->getMessage());
            return response()->json(['message' => 'Error updating service'], 500);
        }
    });
}

    /**
     * Remove the specified resource from storage.
     */
  public function destroy($uuid)
{
    return DB::transaction(function () use ($uuid) {
        try {
            // Encontrar el servicio por su UUID o lanzar una excepción si no se encuentra
            $service = Service::where('service_uuid', $uuid)->firstOrFail();

            // Eliminar la imagen asociada si existe
            if ($service->service_image_path) {
                $pathWithoutAppPublic = str_replace('storage/app/public/', '', $service->service_image_path);
                Storage::disk('public')->delete($pathWithoutAppPublic);
            }

            // Eliminar el servicio
            $service->delete();

            // Eliminar la caché específica del servicio
            $cacheKey = 'service_' . $uuid;
            Cache::forget($cacheKey);

            // Eliminar la caché de la lista de servicios para asegurar consistencia
            Cache::forget('services');

            // Devolver una respuesta JSON con un mensaje de éxito
            return response()->json(['message' => 'Service successfully removed'], 200);
        } catch (ModelNotFoundException $e) {
            // Manejar el caso donde el servicio no fue encontrado
            return response()->json(['message' => 'Service not found'], 404);
        } catch (\Exception $e) {
            // Manejar cualquier otro error y registrar el mensaje de error
            Log::error('An error occurred while removing the service: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while removing the service'], 500);
        }
    });
}



}
