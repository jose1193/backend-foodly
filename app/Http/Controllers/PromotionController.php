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
        
        $businesses = User::findOrFail($this->userId)->businesses;

       
        $allPromotions = collect();

       
        foreach ($businesses as $business) {
            $businessId = $business->id;
            $promotions = Promotion::withTrashed()->where('business_id', $businessId)->orderBy('created_at', 'desc')->get();
            $allPromotions = $allPromotions->concat($promotions);
        }

        return $allPromotions;
    }


public function store(PromotionRequest $request)
    {
        $validatedData = $request->validated();

        DB::beginTransaction();
        try {
            $this->authorizeBusiness($validatedData['business_id']);

            $validatedData['promotion_uuid'] = Uuid::uuid4()->toString();
            $promotion = Promotion::create($validatedData);

           

            $this->refreshCache( "promotion_{$promotion->promotion_uuid}", $this->cacheTime, function () use ($promotion) {
            return $promotion;
            });

            // Obtener todas las promociones nuevamente para actualizar la caché
            $allPromotions = $this->getAllPromotions();

            // Actualizar la caché con todas las promociones
            $this->updatePromotionCache($allPromotions);
            
            DB::commit();

            return response()->json(new PromotionResource($promotion), 200);
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            Log::error('Error creating promotion: ' . $e->getMessage());
            return response()->json(['message' => 'Error creating promotion', 'error' => $e->getMessage()], 500);
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
        $userId = $this->userId;
        $cacheKey = "promotion_{$uuid}";
        
        
        $promotion = $this->getCachedData($cacheKey, $this->cacheTime, function () use ($uuid, $userId) {
            return Promotion::where('promotion_uuid', $uuid)
                            ->whereHas('business', function ($query) use ($userId) {
                                $query->where('user_id', $userId);
                            })->firstOrFail();
        });

        $validatedData = $request->validated();

       
        if (isset($validatedData['business_id']) && $validatedData['business_id'] != $promotion->business_id) {
            return response()->json(['message' => 'You are not authorized to update this promotion.'], 403);
        }

        $promotion->update($validatedData);

        
        $this->refreshCache($cacheKey, $this->cacheTime, function () use ($promotion) {
            return $promotion;
            });

    
        $allPromotions = $this->getAllPromotions();
        // Actualizar la caché con todas las promociones
        $this->updatePromotionCache($allPromotions);

        DB::commit();

        return response()->json(new PromotionResource($promotion), 200);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        DB::rollBack();
        return response()->json(['message' => 'Promotion not found'], 404);
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
            return Promotion::withTrashed()->where('promotion_uuid', $uuid)->firstOrFail();
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
        // Intentar encontrar la promoción por su UUID
        $cacheKey = "promotion_{$uuid}";
       
        $promotion = $this->getCachedData($cacheKey, $this->cacheTime, function () use ($uuid) {
            return Promotion::where('promotion_uuid', $uuid)->firstOrFail();
        });

        $this->invalidateCache($cacheKey);
      
        $promotion->delete();

        $allPromotions = $this->getAllPromotions();

        // Actualizar la caché con todas las promociones
        $this->updatePromotionCache($allPromotions);

        DB::commit();

    
        return response()->json(['message' => 'Promotion deleted successfully'], 200);
    } catch (ModelNotFoundException $e) {
        // Revertir la transacción si la promoción no se encuentra
        DB::rollBack();
        return response()->json(['message' => 'Promotion not found'], 404);
    } catch (\Exception $e) {
        // Revertir la transacción en caso de cualquier otro error
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
        $promotion = Promotion::where('promotion_uuid', $uuid)->onlyTrashed()->first();

       

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
