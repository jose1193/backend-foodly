<?php

namespace App\Http\Controllers;
use App\Http\Controllers\BaseController as BaseController;
use Illuminate\Http\Request;
use App\Models\Promotion;
use App\Models\User;
use App\Models\Business;
use App\Http\Requests\PromotionRequest;
use App\Http\Resources\PromotionResource;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Helpers\ImageHelper;

class PromotionController extends BaseController
{
    protected $cacheKey;
    protected $cacheTime = 720;
    protected $userId;

      // PERMISSIONS USERS
    public function __construct()
{
   $this->middleware('check.permission:Manager')->only(['index','create', 'store','update', 'destroy','updateLogo']);
    
    
  $this->middleware(function ($request, $next) {
            $this->userId = Auth::id();
            $this->cacheKey = 'user_' . $this->userId . '_promotions';
            return $next($request);
        });

}

public function index()
    {
        try {
            $allPromotions = $this->getCachedData($this->cacheKey, $this->cacheTime, function () {
                return $this->getAllPromotions();
            });

            $allPromotions = $allPromotions->sortByDesc('created_at');

            return response()->json(['business_promotions' => PromotionResource::collection($allPromotions)], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching promotions: ' . $e->getMessage());
            return response()->json(['message' => 'Error fetching promotions'], 500);
        }
    }



    private function getAllPromotions()
    {
        return Promotion::whereIn('business_id', 
            User::findOrFail($this->userId)
                ->businesses()
                ->pluck('id')
        )
        ->orderBy('created_at', 'desc')
        ->get();
    }


    public function store(PromotionRequest $request)
    {
        $validatedData = $request->validated();

        DB::beginTransaction();
        try {
        $business = Business::where('business_uuid', $validatedData['business_uuid'])->firstOrFail();
        $this->authorizeBusiness($business->id);

        $validatedData['business_id'] = $business->id;
        unset($validatedData['business_uuid']);
        $validatedData['uuid'] = Uuid::uuid4()->toString();

        $promotion = Promotion::create($validatedData);

        if ($request->filled('promo_active_days')) {
            $activeDays = $request->input('promo_active_days');
            $promotion->activeDay()->create([
                'day_0' => $activeDays['day_0'] ?? false,
                'day_1' => $activeDays['day_1'] ?? false,
                'day_2' => $activeDays['day_2'] ?? false,
                'day_3' => $activeDays['day_3'] ?? false,
                'day_4' => $activeDays['day_4'] ?? false,
                'day_5' => $activeDays['day_5'] ?? false,
                'day_6' => $activeDays['day_6'] ?? false
            ]);
        }

        $this->refreshCache("promotion_{$promotion->uuid}", $this->cacheTime, fn() => $promotion);
        $this->updatePromotionCache($this->getAllPromotions());

        DB::commit();
        return response()->json(new PromotionResource($promotion), 200);
        } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error creating promotion: ' . $e->getMessage());
        return response()->json(['message' => 'Error creating promotion'], 500);
        }
    }


    
    


private function authorizeBusiness($businessId)
{
   
    $isUserBusiness = Business::where('user_id', $this->userId)->where('id', $businessId)->exists();

    if (!$isUserBusiness) {
        abort(403, 'The provided business_id does not belong to the authenticated user');
    }
}


public function update(PromotionRequest $request, $uuid) 
    {
    

        DB::beginTransaction();
        try {
            $promotion = Promotion::where('uuid', $uuid)
            ->whereHas('business', function($q) {
                $q->where('user_id', $this->userId);
            })->firstOrFail();

            $promotion->update($request->validated());

            if ($request->filled('promo_active_days')) {
                $activeDays = $request->input('promo_active_days');
                $promotion->activeDay()->updateOrCreate(
                ['promotion_id' => $promotion->id],
                [
                    'day_0' => $activeDays['day_0'] ?? false,
                    'day_1' => $activeDays['day_1'] ?? false,
                    'day_2' => $activeDays['day_2'] ?? false,
                    'day_3' => $activeDays['day_3'] ?? false,
                    'day_4' => $activeDays['day_4'] ?? false,
                    'day_5' => $activeDays['day_5'] ?? false,
                    'day_6' => $activeDays['day_6'] ?? false
                ]
                );
            }

            $this->refreshCache("promotion_{$uuid}", $this->cacheTime, fn() => $promotion);
            $this->updatePromotionCache($this->getAllPromotions());

            DB::commit();
            return response()->json(new PromotionResource($promotion), 200);
            } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating promotion: ' . $e->getMessage());
            return response()->json(['message' => 'Error updating promotion'], 500);
        }
    }





public function show($uuid)
{
    try {
        $cacheKey = "promotion_{$uuid}";
        
        $promotion = $this->getCachedData($cacheKey, $this->cacheTime, function () use ($uuid) {
            return Promotion::where('uuid', $uuid)->firstOrFail();
        });

        return response()->json(new PromotionResource($promotion), 200);
    } catch (ModelNotFoundException $e) {
        return response()->json(['message' => 'Promotion not found'], 404);
    } catch (\Exception $e) {
        Log::error('Error fetching promotion: ' . $e->getMessage());
        return response()->json(['message' => 'Error fetching promotion'], 500);
    }
}





public function destroy($uuid)
{
    DB::beginTransaction();
    try {
        $cacheKey = "promotion_{$uuid}";
       
        $promotion = $this->getCachedData($cacheKey, $this->cacheTime, function () use ($uuid) {
            return Promotion::with('promotionMedia')->where('uuid', $uuid)->firstOrFail();
        });

        // Eliminar los archivos físicos de los medias
        foreach ($promotion->promotionMedia as $media) {
            if ($media->business_promo_media_url) {
                ImageHelper::deleteFileFromStorage($media->business_promo_media_url);
            }
            // Invalidar el cache de cada media
            $this->invalidateCache("promotion_media_{$media->uuid}");
        }

        $this->invalidateCache($cacheKey);
      
        $promotion->delete();

        $allPromotions = $this->getAllPromotions();

        // Actualizar la caché con todas las promociones
        $this->updatePromotionCache($allPromotions);

        DB::commit();
    
        return response()->json(['message' => 'Promotion and associated media deleted successfully'], 200);
    } catch (ModelNotFoundException $e) {
        DB::rollBack();
        return response()->json(['message' => 'Promotion not found'], 404);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error deleting promotion: ' . $e->getMessage());
        return response()->json(['message' => 'Error deleting promotion'], 500);
    }
}


public function restore($uuid)
{
    DB::beginTransaction();
    try {
       
         // Buscar la promoción eliminada con el UUID proporcionado
        $promotion = Promotion::where('uuid', $uuid)->onlyTrashed()->first();

       

        if (!$promotion) {
            DB::rollBack();
            return response()->json(['message' => 'Promotion not found in trash'], 404);
        }

        // Restaurar la promoción eliminada
        $promotion->restore();

        $cacheKey = "promotion_{$uuid}";

        $this->refreshCache($cacheKey, $this->cacheTime, function () use ($promotion) {
            return $promotion;
        });

        $allPromotions = $this->getAllPromotions();

        // Actualizar la caché con todas las promociones
        $this->updatePromotionCache($allPromotions);

        DB::commit();

       

        // Devolver una respuesta JSON con el mensaje y el recurso de la promoción restaurada
        return response()->json(new PromotionResource($promotion), 200);
    } catch (\Exception $e) {
        DB::rollBack();
        // Manejar cualquier excepción y devolver una respuesta de error
        Log::error('Error occurred while restoring Promotion: ' . $e->getMessage());
        return response()->json(['message' => 'Error occurred while restoring Promotion'], 500);
    }
}


    private function updatePromotionCache($allPromotions)
    {
        $this->refreshCache($this->cacheKey, $this->cacheTime, function () use ($allPromotions) {
            return $allPromotions;
        });
    }

}
