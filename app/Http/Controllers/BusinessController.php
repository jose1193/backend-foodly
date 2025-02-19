<?php

namespace App\Http\Controllers;
use App\Http\Controllers\BaseController as BaseController;
use Illuminate\Http\Request;
use App\Models\Business;
use App\Models\BusinessMenu;
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
   $this->middleware('check.permission:Customer')->only(['index', 'store', 'update', 'destroy','updateLogo']);
  
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


        return response()->json([
            'business' => BusinessResource::collection($businesses)
        ], 200);

    } catch (QueryException $e) {
        Log::error('Database error: ' . $e->getMessage());
        return response()->json(['message' => 'A database error occurred. Please try again later.'. $e->getMessage()], 500);
    } catch (\Exception $e) {
        Log::error('Error retrieving businesses: ' . $e->getMessage());
        return response()->json(['message' => 'An error occurred while retrieving businesses. Please try again later.' . $e->getMessage()], 500);
    }
}

private function updateBusinessCache($userId)
{
    $businesses = Business::withTrashed()->where('user_id', $userId)->orderBy('id', 'desc')->get();

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

                // Create default menu with just business_id
                $menu = BusinessMenu::create([
                    'uuid' => Uuid::uuid4()->toString(),
                    'business_id' => $business->id
                ]);

                // Cache the new menu
                $menuCacheKey = "business_menu_{$menu->uuid}";
                Cache::put($menuCacheKey, $menu, now()->addMinutes(720));

                // Update business menus cache
                $businessMenusCacheKey = "user_{$userId}_business_{$business->id}_business_menus";
                Cache::put($businessMenusCacheKey, collect([$menu]), now()->addMinutes(720));

                // Attach services
                $services = collect($data['business_services'] ?? []);
                $business->services()->attach($services);
                // Create business hours
                //$hours = collect($data['business_hours'] ?? []);
                //$hours->each(function ($hour) use ($business) {
                //$hour['business_id'] = $business->id;
            
                //$business->businessHours()->create($hour);
                //});
                // Create business hours if provided
                $businessOpeningHours = $request->input('business_opening_hours');
                if (is_array($businessOpeningHours)) {
                    foreach ($businessOpeningHours as $day => $hours) {
                        if (!is_array($hours)) continue;

                        $hours = array_filter($hours, function($time, $key) {
                            return !is_null($time) && in_array($key, ['open_a', 'close_a', 'open_b', 'close_b']);
                        }, ARRAY_FILTER_USE_BOTH);

                        $hour = [
                            'business_id' => $business->id,
                            'day' => $day,
                            'open_a' => $hours['open_a'] ?? null,
                            'close_a' => $hours['close_a'] ?? null,
                            'open_b' => $hours['open_b'] ?? null,
                            'close_b' => $hours['close_b'] ?? null,
                        ];

                        // Verify each opening has a corresponding closing time
                        if (($hour['open_a'] && $hour['close_a']) || ($hour['open_b'] && $hour['close_b'])) {
                            $business->businessHours()->create($hour);
                        }
                    }
                }

                // Dispatch welcome email
                SendWelcomeEmailBusiness::dispatch($business->user, $business);

                // Handle caching
                $cacheKey = 'business_' . $business->business_uuid;
                $this->refreshCache($cacheKey, $this->cacheTime, fn() => $business);
                $this->updateBusinessCache($userId);

                // Include menu in response
                $business->load('businessMenus');
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
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
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

     if ($request->filled('business_opening_hours')) {
    $businessOpeningHours = $request->input('business_opening_hours');

// Inicializar un array para almacenar las horas procesadas
$processedHours = [];

foreach ($businessOpeningHours as $day => $hours) {
    // Filtrar y validar los horarios
    $hours = array_filter($hours, function($time, $key) {
        return !is_null($time) && in_array($key, ['open_a', 'close_a', 'open_b', 'close_b']);
    }, ARRAY_FILTER_USE_BOTH);

    $hour = [
        'business_id' => $business->id,
        'day' => $day,
        'open_a' => $hours['open_a'] ?? null,
        'close_a' => $hours['close_a'] ?? null,
        'open_b' => $hours['open_b'] ?? null,
        'close_b' => $hours['close_b'] ?? null,
    ];

    // Verificar que cada apertura tiene un cierre correspondiente
    if (($hour['open_a'] && !$hour['close_a']) || (!$hour['open_a'] && $hour['close_a'])) {
        continue; // O manejar el error según sea necesario
    }
    if (($hour['open_b'] && !$hour['close_b']) || (!$hour['open_b'] && $hour['close_b'])) {
        continue; // O manejar el error según sea necesario
    }

    // Guardar los datos en el array procesado para respuesta
    $processedHours[] = $hour;

    // Buscar si ya existe un registro para este día
    $existingHour = $business->businessHours()->where('day', $day)->first();

    if ($existingHour) {
        // Actualizar los campos existentes
        $existingHour->update($hour);
    } else {
        // Si no existe un registro para este día, crear uno nuevo
        $business->businessHours()->create($hour);
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
         return response()->json(['message' => 'An error occurred: ' . $e->getMessage()], 500);
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
