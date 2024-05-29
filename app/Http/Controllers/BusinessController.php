<?php

namespace App\Http\Controllers;
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
use Illuminate\Routing\Controller as BaseController;



class BusinessController extends BaseController
{

     // PERMISSIONS USERS
    public function __construct()
{
   $this->middleware('check.permission:Manager')->only(['index', 'store', 'update', 'destroy','updateLogo']);

}

public function index()
{
    try {
        $userId = Auth::id();
        $cacheKey = 'user_' . $userId . '_business';

        $businesses = $this->getCachedData($cacheKey, 3600, function () use ($userId) {
            return Business::withTrashed()->where('user_id', $userId)->orderBy('id', 'desc')->get();
        });

        if ($businesses->isEmpty()) {
            return response()->json(['message' => 'No businesses found'], 404);
        }

        return response()->json(['business' => BusinessResource::collection($businesses)], 200);
    } catch (QueryException $e) {
        Log::error('Database error: ' . $e->getMessage());
        return response()->json(['message' => 'Database error: ' . $e->getMessage()], 500);
    } catch (\Exception $e) {
        Log::error('Error retrieving business: ' . $e->getMessage());
        return response()->json(['message' => 'Error retrieving business'], 500);
    }
}



public function show($uuid)
{
    try {
        $cacheKey = 'business_' . $uuid;

        $business = $this->getCachedData($cacheKey, 3600, function () use ($uuid) {
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

    try {
        return DB::transaction(function () use ($request, $data) {
            // Generar un UUID
            $data['business_uuid'] = Uuid::uuid4()->toString();

            // Obtener el ID del usuario actualmente autenticado
            $data['user_id'] = Auth::id();

            // Guardar la foto del negocio si existe
            if ($request->hasFile('business_logo')) {
                $image = $request->file('business_logo');
                $photoPath = ImageHelper::storeAndResize($image, 'public/business_logos');
                $data['business_logo'] = $photoPath;
            }

            // Crear el negocio
            $business = Business::create($data);

            // Verificar y ajustar el formato de business_services
            $services = $data['business_services'] ?? [];
            if (!is_array($services)) {
                $services = [$services];  // Convertir a array si no lo es
            }

            // Asociar servicios con el negocio usando la tabla pivot
            $business->services()->attach($services);

            // Enviar correo electrónico de manera asincrónica
            Mail::to($business->user->email)->send(new WelcomeMailBusiness($business->user, $business));

            // Manejo de caché
            $this->invalidateUserBusinessesCache($data['user_id']);
            $this->putCachedData('business_' . $business->business_uuid, $business, 60);

            // Devolver una respuesta adecuada
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

        $business = $this->getCachedData($cacheKey, 3600, function () use ($uuid) {
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
            $this->invalidateCache($cacheKey);
            $this->putCachedData($cacheKey, $business->fresh(), 60);

            
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
            $business = $this->getCachedData($cacheKey, 600, function () use ($uuid) {
                return auth()->user()->businesses()->where('business_uuid', $uuid)->firstOrFail();
            });

            $business->update($request->validated());

            if ($request->filled('business_services')) {
                $serviceIds = $request->input('business_services');
                if (!is_array($serviceIds)) {
                    $serviceIds = [$serviceIds];
                }
                $business->services()->sync($serviceIds);
            }

            $this->putCachedData($cacheKey, $business->fresh(), 60);

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
        $business = $this->getCachedData("business_{$uuid}", 600, function () use ($uuid) {
            return Business::where('business_uuid', $uuid)->firstOrFail();
        });

        $this->invalidateCache("business_{$uuid}");
        $business->delete();

        return response()->json(['message' => 'Business deleted successfully'], 200);
    } catch (\Exception $e) {
        Log::error('Error deleting business: ' . $e->getMessage());
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
    }
}

public function restore($uuid)
{
    try {
        $business = auth()->user()->businesses()->where('business_uuid', $uuid)->onlyTrashed()->first();

        if (!$business) {
            return response()->json(['message' => 'Business not found in trash or unauthorized'], 404);
        }

        if (!$business->trashed()) {
            return response()->json(['message' => 'Business already restored'], 400);
        }

        $business->restore();
        $this->putCachedData("business_{$uuid}", $business, 60);

        return response()->json(new BusinessResource($business), 200);
    } catch (\Exception $e) {
        Log::error('Error restoring business: ' . $e->getMessage());
        return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
    }
}





private function getCachedData($key, $minutes, \Closure $callback)
{
    return Cache::remember($key, $minutes, $callback);
}

private function putCachedData($key, $data, $minutes)
{
    Cache::put($key, $data, now()->addMinutes($minutes));
}

private function invalidateCache($key)
{
    Cache::forget($key);
}

private function invalidateUserBusinessesCache($userId)
{
    $cacheKey = 'user_' . $userId . '_business';
    $this->invalidateCache($cacheKey);
}




}
