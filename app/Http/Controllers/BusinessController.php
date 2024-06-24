<?php

namespace App\Http\Controllers;
use App\Http\Controllers\BaseController as BaseController;
use Illuminate\Http\Request;
use App\Models\Business;

use App\Http\Requests\BusinessRequest;
use App\Http\Resources\BusinessResource;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;
use App\Models\BusinessCoverImage;
use App\Models\User;

use Illuminate\Support\Facades\Auth;
use App\Http\Requests\UpdateBusinessLogoRequest;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeMailBusiness;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

use App\Helpers\ImageHelper;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Jobs\SendWelcomeEmailBusiness;



class BusinessController extends BaseController
{
    protected $cacheKey;
    
    protected $cacheTime = 720;
    protected $userId;

    
    public function __construct()
{
   $this->middleware('check.permission:Manager')->only(['index', 'store', 'update', 'destroy','updateLogo']);
  
  $this->middleware(function ($request, $next) {
            $this->userId = Auth::id();
            $this->cacheKey = 'user_' . $this->userId . '_business';
            return $next($request);
        });


}

public function index()
{
    try {
        $userId = $this->userId;
        $businesses = $this->getCachedData($this->cacheKey, $this->cacheTime, function () use ($userId) {
            return $this->updateBusinessCache($userId);
        });

        if ($businesses->isEmpty()) {
            return response()->json(['message' => 'No businesses found'], 404);
        }

        return response()->json(['business' => BusinessResource::collection($businesses)], 200);
    } catch (QueryException $e) {
        Log::error('Database error: ' . $e->getMessage());
        return response()->json(['message' => 'A database error occurred. Please try again later.'], 500);
    } catch (\Exception $e) {
        Log::error('Error retrieving business: ' . $e->getMessage());
        return response()->json(['message' => 'An error occurred while retrieving businesses. Please try again later.'], 500);
    }
}

private function updateBusinessCache($userId)
{
    $businesses = Business::withTrashed()->where('user_id', $userId)->orderBy('id', 'desc')->get();

    if ($businesses->isEmpty()) {
        throw new \Exception('No businesses found');
    }

    $this->refreshCache($this->cacheKey, $this->cacheTime, function () use ($businesses) {
        return $businesses;
    });

    return $businesses;
}




public function show($uuid)
{
    try {
        $cacheKey = 'business_' . $uuid;
       

        $business = $this->getCachedData($cacheKey, $this->cacheTime, function () use ($uuid) {
            return Business::withTrashed()->where('business_uuid', $uuid)->firstOrFail();
        });

        return response()->json(new BusinessResource($business), 200);
    } catch (QueryException $e) {
        Log::error('Database error: ' . $e->getMessage());
        return response()->json(['message' => 'Database error: ' . $e->getMessage()], 500);
    } catch (\Exception $e) {
        Log::error('Error retrieving business: ' . $e->getMessage());
        return response()->json(['message' => 'Error retrieving business'], 500);
    }
}


public function store(BusinessRequest $request)
{
    $data = $request->validated();

    // Ensure the user ID is set
    $userId = $this->userId;
    if (!$userId) {
        Log::error('Authenticated user not found.');
        return response()->json(['message' => 'Authenticated user not found.'], 401);
    }

    try {
        return DB::transaction(function () use ($request, $data, $userId) {
            // Generate a UUID
            $data['business_uuid'] = Uuid::uuid4()->toString();
            $data['user_id'] = $userId;

            // Save the business logo if it exists
            if ($request->hasFile('business_logo')) {
                $image = $request->file('business_logo');
                $data['business_logo'] = ImageHelper::storeAndResize($image, 'public/business_logos');
            }

            // Create the business
            $business = Business::create($data);

            // Attach services
            $services = collect($data['business_services'] ?? []);
            $business->services()->attach($services);

            // Create business hours
            //$hours = collect($data['business_hours'] ?? []);
            //$hours->each(function ($hour) use ($business) {
                //$hour['business_id'] = $business->id;
            
                //$business->businessHours()->create($hour);
            //});


        // Suponiendo que $data contiene los datos enviados desde el formulario
        $businessHoursData = $data['business_hours'] ?? [];
        // Iterar sobre los días y crear cada registro de business hours
        foreach ($businessHoursData as $day => $hours) {
            // Verificar si hay datos válidos para el día actual
            if (!empty($hours['open_a']) && !empty($hours['close_a'])) {
                // Crear un nuevo array con los datos relevantes
                $businessHourData = [
                    'business_id' => $business->id,
                    'day' => $day,  // Puedes guardar el día si lo necesitas
                    'open_a' => $hours['open_a'],
                    'close_a' => $hours['close_a'],
                    'open_b' => $hours['open_b'],
                    'close_b' => $hours['close_b'],
            
                ];

                    // Añadir open_b y close_b si existen
                    if (!empty($hours['open_b']) && !empty($hours['close_b'])) {
                    $businessHourData['open_b'] = $hours['open_b'];
                    $businessHourData['close_b'] = $hours['close_b'];
                }

                    // Crear el registro de business hour
                    $business->businessHours()->create($businessHourData);
                    }
          }


            // Dispatch welcome email
            SendWelcomeEmailBusiness::dispatch($business->user, $business);

            // Handle caching
            $cacheKey = 'business_' . $business->business_uuid;
            $this->refreshCache($cacheKey, $this->cacheTime, fn() => $business);
            $this->updateBusinessCache($userId);

            return response()->json(new BusinessResource($business), 200);
        });
    } catch (QueryException $e) {
        Log::error('Database error storing business: ' . $e->getMessage());
        return response()->json(['message' => 'A database error occurred: ' . $e->getMessage()], 500);
    } catch (MailException $e) {
        Log::error('Mail error storing business: ' . $e->getMessage());
        return response()->json(['message' => 'A mail error occurred: ' . $e->getMessage()], 500);
    } catch (\Exception $e) {
        Log::error('Error storing business: ' . $e->getMessage());
        return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], 500);
    }
}



public function updateLogo(UpdateBusinessLogoRequest $request, $uuid)
{
    try {
        $cacheKey = 'business_' . $uuid;
       

        $business = $this->getCachedData($cacheKey, $this->cacheTime, function () use ($uuid) {
            return Business::withTrashed()->where('business_uuid', $uuid)->firstOrFail();
        });

       

        if ($request->hasFile('business_logo')) {
            $image = $request->file('business_logo');

              if ($business->business_logo) {
                    ImageHelper::deleteFileFromStorage($business->business_logo);
                    }


            $photoPath = ImageHelper::storeAndResize($image, 'public/business_logos');
            $business->business_logo = $photoPath;
            $business->save();

            // Limpiar la caché del negocio
            $this->refreshCache($cacheKey, $this->cacheTime, function () use ($business) {
            return $business;
            });

             // Actualizar el caché
            $this->updateBusinessCache($this->userId);

            
        }

        return response()->json(new BusinessResource($business), 200);
    } catch (\Exception $e) {
        Log::error('Error updating business logo: ' . $e->getMessage());
        return response()->json(['error' => 'An error occurred: '], 500);
    }
}


public function update(BusinessRequest $request, $uuid)
{
    try {
        return DB::transaction(function () use ($request, $uuid) {
            $cacheKey = "business_{$uuid}";

            $business = $this->getCachedData($cacheKey, $this->cacheTime, function () use ($uuid) {
                return auth()->user()->businesses()->where('business_uuid', $uuid)->firstOrFail();
            });

            $business->update($request->validated());

            if ($request->filled('business_services')) {
                $serviceIds = collect($request->input('business_services'))->toArray();
                $business->services()->sync($serviceIds);
            }

        //if ($request->filled('business_hours')) {
                //$hours = collect($request->input('business_hours'))->toArray();
            
               // $business->businessHours()->delete();
               // $business->businessHours()->createMany($hours);
            //}

       if ($request->filled('business_hours')) {
    // Convertir los datos de business_opening_hours en un array
    $businessHoursData = $request->input('business_hours');

    // Iterar sobre los datos de business_opening_hours recibidos
    foreach ($businessHoursData as $day => $hourData) {
        // Buscar y actualizar el registro existente si existe
        $businessHour = $business->businessHours()->where('day', $day)->first();

        if ($businessHour) {
            // Actualizar los campos existentes
            $businessHour->update([
                'open_a' => $hourData['open_a'],
                'close_a' => $hourData['close_a'],
                'open_b' => $hourData['open_b'],
                'close_b' => $hourData['close_b'],
            ]);
        } else {
            // Si no existe un registro para este día, crear uno nuevo
            $business->businessHours()->create([
                'day' => $day,
                'open_a' => $hourData['open_a'],
                'close_a' => $hourData['close_a'],
                'open_b' => $hourData['open_b'],
                'close_b' => $hourData['close_b'],
            ]);
        }
    }
}



            $this->refreshCache($cacheKey, $this->cacheTime, fn() => $business);
            $this->updateBusinessCache($this->userId);

            return response()->json(new BusinessResource($business->fresh()), 200);
        });
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        Log::warning("Business with UUID {$uuid} not found for user ID " . auth()->id());
        return response()->json(['message' => 'Business not found'], 404);
    } catch (\Exception $e) {
        Log::error("Error updating business with UUID {$uuid}: " . $e->getMessage());
        return response()->json(['message' => 'An error occurred'], 500);
    }
}



public function destroy($uuid)
{
    try {

        $cacheKey = "business_{$uuid}";
       

        $business = $this->getCachedData($cacheKey, $this->cacheTime, function () use ($uuid) {
            return Business::where('business_uuid', $uuid)->firstOrFail();
        });

        
        $business->delete();

        $this->refreshCache($cacheKey, $this->cacheTime, function () use ($business) {
            return $business;
        });

        // Actualizar el caché
        $this->updateBusinessCache($this->userId);

        return response()->json(['message' => 'Business deleted successfully'], 200);
    } catch (\Exception $e) {
        Log::error('Error deleting business: ' . $e->getMessage());
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
    }
}



public function restore($uuid)
{
    try {
        $cacheKey = "business_{$uuid}";
        

        $business = auth()->user()->businesses()->where('business_uuid', $uuid)->onlyTrashed()->first();

        if (!$business) {
            return response()->json(['message' => 'Business not found in trash or unauthorized'], 404);
        }

        if (!$business->trashed()) {
            return response()->json(['message' => 'Business already restored'], 400);
        }

        $business->restore();

        $this->refreshCache($cacheKey, $this->cacheTime, function () use ($business) {
            return $business;
        });

        // Actualizar el caché
        $this->updateBusinessCache($this->userId);

        return response()->json(new BusinessResource($business), 200);
    } catch (\Exception $e) {
        Log::error('Error restoring business: ' . $e->getMessage());
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
    }
}




     
}
