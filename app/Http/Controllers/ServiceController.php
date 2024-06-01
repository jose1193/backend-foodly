<?php

namespace App\Http\Controllers;
use App\Http\Controllers\BaseController as BaseController;
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





class ServiceController extends BaseController
{

    protected $cacheKey;
    
    protected $cacheTime = 720;
    protected $userId;

    public function __construct()
{
   $this->middleware('check.permission:Super Admin')->only(['store', 'update', 'destroy','updateImage','show']);

    $this->middleware(function ($request, $next) {
            $this->userId = Auth::id();
            $this->cacheKey = 'services';
            return $next($request);
        });
}


    /**
     * Display a listing of the resource.
     */
   public function index()
{
    try {
        // Utiliza Cache::remember para almacenar en caché los resultados
        $services = $this->getCachedData($this->cacheKey, $this->cacheTime, function () {
            return $this->retrieveServices();
        });

        return response()->json(['services' => ServiceResource::collection($services)], 200);
    } catch (\Exception $e) {
        Log::error('Error retrieving services: ' . $e->getMessage());
        return response()->json(['error' => 'An error occurred while retrieving services'], 500);
    }
}


private function retrieveServices()
{
    return Service::orderBy('id', 'desc')->get();
}

// Actualización específica de caché 
private function updateServicesCache()
{
    $this->refreshCache($this->cacheKey, $this->cacheTime, function () {
        return $this->retrieveServices();
    });
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


       
         // Actualizar la caché del servicio específico
         $this->updateServicesCache();



            return $this->successfulResponse($service);
        } catch (\Throwable $e) {
            Log::error('An error occurred while creating service: ' . $e->getMessage());
            return $this->errorResponse();
        }
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
    try {
        return DB::transaction(function () use ($request, $uuid) {
            $cacheKey = "service_{$uuid}";

           
            $service = $this->getCachedData($cacheKey, $this->cacheTime, function () use ($uuid) {
                return Service::where('service_uuid', $uuid)->firstOrFail();
            });

          
            if ($request->hasFile('service_image_path')) {
                
                $image = $request->file('service_image_path');

                
                if ($service->service_image_path) {
                    ImageHelper::deleteFileFromStorage($service->service_image_path);
                }
                $photoPath = ImageHelper::storeAndResize($image, 'public/services_images');

                $service->service_image_path = $photoPath;
                $service->save();

                 // Actualiza la caché
                $this->refreshCache($cacheKey, $this->cacheTime, function () use ($service) {
                    return $service;
                });

                $this->updateServicesCache();

            }

            return response()->json(new ServiceResource($service), 200);
        });
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        Log::warning("Service with UUID {$uuid} not found");
        return response()->json(['error' => 'Service not found'], 404);
    } catch (\Throwable $e) {
       
        Log::error('Error updating service image: ' . $e->getMessage());
        return response()->json(['error' => 'Error updating service image'], 500);
    }
}




    public function show($uuid)
{
    try {
        $cacheKey = "service_{$uuid}";
      
       
        $service = $this->getCachedData($cacheKey, $this->cacheTime, function () use ($uuid) {
            return Service::where('service_uuid', $uuid)->firstOrFail();
        });

        
        return response()->json(new ServiceResource($service), 200);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
       
        Log::warning("Service with UUID {$uuid} not found");
        return response()->json(['message' => 'Service not found'], 404);
    } catch (\Exception $e) {
      
        Log::error('Error retrieving service: ' . $e->getMessage());
        return response()->json(['message' => 'An error occurred while retrieving the service'], 500);
    }
}



    /**
     * Update the specified resource in storage.
     */
 public function update(ServiceRequest $request, $uuid)
{
    try {
        $cacheKey = "service_{$uuid}";
        $cacheTime = $this->cacheTime;

        // Obtén el servicio correspondiente al UUID
         $service = $this->getCachedData($cacheKey, $cacheTime, function () use ($uuid) {
                return Service::where('service_uuid', $uuid)->firstOrFail();
            });
      

        return DB::transaction(function () use ($request, $service, $cacheKey, $cacheTime, $uuid) {
            // Actualiza el servicio con los datos validados
            $service->update($request->validated());

            // Actualiza la caché
            $this->refreshCache($cacheKey, $cacheTime, function () use ($service) {
                    return $service;
                });

            $this->updateServicesCache();

            return response()->json(new ServiceResource($service), 200);
        });
    } catch (ModelNotFoundException $e) {
        return response()->json(['message' => 'Service not found'], 404);
    } catch (\Exception $e) {
        Log::error('Error updating service: ' . $e->getMessage());
        return response()->json(['message' => 'Error updating service'], 500);
    }
}




    /**
     * Remove the specified resource from storage.
     */
 public function destroy($uuid)
{
    // Validar UUID (si es necesario)
    if (!Str::isUuid($uuid)) {
        return response()->json(['message' => 'Invalid UUID'], 400);
    }

    return DB::transaction(function () use ($uuid) {
        try {
           
            $cacheKey = "service_{$uuid}";
           
           
            $service = $this->getCachedData($cacheKey, $this->cacheTime, function () use ($uuid) {
                return Service::where('service_uuid', $uuid)->firstOrFail();
            });

           
            if ($service->service_image_path) {
                ImageHelper::deleteFileFromStorage($service->service_image_path);
            }

            $service->delete();

            
             // Actualiza la caché
            $this->refreshCache($cacheKey, $this->cacheTime, function () use ($service) {
                    return $service;
                });

            $this->updateServicesCache();

            return response()->json(['message' => 'Service successfully removed'], 200);
        } catch (ModelNotFoundException $e) {
           
            Log::warning('Service not found: ' . $uuid);
            return response()->json(['message' => 'Service not found'], 404);
        } catch (\Exception $e) {
         
            Log::error('An error occurred while removing the service: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while removing the service'], 500);
        }
    });
}



}
