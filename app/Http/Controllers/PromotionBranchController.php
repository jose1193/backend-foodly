<?php

namespace App\Http\Controllers;
use App\Http\Controllers\BaseController as BaseController;
use Illuminate\Http\Request;
use App\Models\PromotionBranch;
use App\Models\User;
use App\Models\BusinessBranch;
use App\Models\Business;
use App\Http\Requests\PromotionBranchRequest;
use App\Http\Resources\PromotionBranchResource;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;


class PromotionBranchController extends BaseController
{
    protected $cacheKey;
    protected $cacheTime = 720;
    protected $userId;

    public function __construct()
{
   $this->middleware('check.permission:Manager')->only(['index','create', 'store','update', 'destroy','updateLogo']);

     $this->middleware(function ($request, $next) {
            $this->userId = Auth::id();
            $this->cacheKey = 'user_' . $this->userId . '_branch_promotions';
            return $next($request);
        });

}


    /**
     * Display a listing of the resource.
     */
   public function index()
{
    try {
        $userId = $this->userId;

        // Intentar obtener las promociones desde la caché
        $promotionsBranches = $this->getCachedPromotions($userId);

        // Devolver todas las promociones como respuesta JSON
        return response()->json(['branch_promotions' => PromotionBranchResource::collection($promotionsBranches)], 200);
    } catch (\Exception $e) {
        Log::error('Error fetching promotions branch: ' . $e->getMessage());
        return response()->json(['message' => 'Error fetching promotions branch: '], 500);
    }
}

private function getCachedPromotions($userId)
{
    return $this->getCachedData($this->cacheKey, $this->cacheTime, function () use ($userId) {
        // Obtener todas las promociones de las sucursales asociadas a los negocios del usuario autenticado
        return $this->getAllPromotions($userId);
    });
}

private function getAllPromotions($userId)
{
    $promotionsBranches = Business::where('user_id', $userId)
        ->with(['branches.promotionsbranches' => function($query) {
            $query->orderBy('id', 'desc');
        }])
        ->get()
        ->pluck('branches')
        ->flatten()
        ->pluck('promotionsbranches')
        ->flatten();

    return $promotionsBranches;
}




    /**
     * Store a newly created resource in storage.
     */
   public function store(PromotionBranchRequest $request)
{
    try {
        DB::beginTransaction();

        // Obtener el ID del usuario autenticado
        $userId = Auth::id();

        // Validar los datos de la solicitud
        $validatedData = $request->validated();

        // Obtener el business_branch_id proporcionado en la solicitud
        $businessBranchId = $validatedData['branch_id'];

        // Verificar si el business_branch_id pertenece al usuario autenticado
        $isUserBranch = BusinessBranch::where('id', $businessBranchId)
            ->whereHas('business', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })->exists();

        if (!$isUserBranch) {
            return response()->json(['message' => 'The provided branch_id does not belong to the authenticated user'], 403);
        }

        // Generar un UUID para la promoción de la sucursal
        $validatedData['promotion_branch_uuid'] = Uuid::uuid4()->toString();

        // Crear la promoción de la sucursal
        $promotionBranch = PromotionBranch::create($validatedData);

        //Cache
        $this->refreshCache( "branch_promotion_{$promotionBranch->promotion_branch_uuid}", $this->cacheTime, function () use ($promotionBranch) {
        return $promotionBranch;
        });

        
        $this->updatePromotionCache($this->getAllPromotions($this->userId));

        DB::commit();

       

        return response()->json(new PromotionBranchResource($promotionBranch), 200);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error storing promotions branch: ' . $e->getMessage());
        return response()->json(['message' => 'Error storing promotion'], 500);
    }
}





    /**
     * Display the specified resource.
     */
    public function show($uuid)
{
    try {
        // Intentar obtener la promoción desde la caché
        $promotionBranch = $this->getCachedData('branch_promotion_' . $uuid, $this->cacheTime, function () use ($uuid) {
            // Buscar la promoción de sucursal por su UUID, incluyendo las promociones eliminadas
            return PromotionBranch::withTrashed()->where('promotion_branch_uuid', $uuid)->firstOrFail();
        });

        // Devolver la promoción de sucursal como respuesta JSON
        return response()->json(new PromotionBranchResource($promotionBranch), 200);
    } catch (ModelNotFoundException $e) {
        // Manejar el caso en que la promoción no se encuentre y devolver un mensaje de error
        return response()->json(['message' => 'Promotion not found'], 404);
    } catch (\Exception $e) {
        // Manejar cualquier otra excepción y devolver un mensaje de error genérico
        Log::error('Error fetching branch promotion: ' . $e->getMessage());
        return response()->json(['message' => 'Error fetching branch promotion'], 500);
    }
}




    /**
     * Update the specified resource in storage.
     */
 public function update(PromotionBranchRequest $request, $uuid)
{
    DB::beginTransaction();
    try {
        $userId = Auth::id();

        // Asegurarse de que la promoción pertenece al usuario autenticado
        $promotionBranch = PromotionBranch::where('promotion_branch_uuid', $uuid)
            ->whereHas('branches.business', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->firstOrFail();

        $validatedData = $request->validated();
        $promotionBranch->update($validatedData);

        
        //Cache
        $this->refreshCache( "branch_promotion_{$uuid}", $this->cacheTime, function () use ($promotionBranch) {
        return $promotionBranch;
        });

        $this->updatePromotionCache($this->getAllPromotions($this->userId));

        DB::commit();

        return response()->json(new PromotionBranchResource($promotionBranch), 200);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // Si no se encuentra la promoción, proporcionar una respuesta adecuada
        DB::rollBack();
        Log::warning('PromotionBranch not found for UUID: ' . $uuid . ' by user ID: ' . $userId);
        return response()->json(['message' => 'Promotion branch not found.'], 404);
    } catch (\Exception $e) {
        // En caso de error, revertir la transacción y loguear el error
        DB::rollBack();
        Log::error('Error updating branch promotion: ' . $e->getMessage());
        return response()->json(['message' => 'An error occurred while updating branch promotion. Please try again later.'], 500);
    }
}




    /**
     * Remove the specified resource from storage.
     */
    

public function destroy($uuid)
{
    DB::beginTransaction();
    try {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $promotionBranch = PromotionBranch::where('promotion_branch_uuid', $uuid)->firstOrFail();

        $promotionBranch->delete();


        //Cache
        $this->refreshCache( "branch_promotion_{$uuid}", $this->cacheTime, function () use ($promotionBranch) {
        return $promotionBranch;
        });

        $this->updatePromotionCache($this->getAllPromotions($this->userId));


        DB::commit();

        return response()->json(['message' => 'Promotion branch deleted successfully'], 200);
    } catch (ModelNotFoundException $e) {
        DB::rollBack();
        return response()->json(['message' => 'Promotion branch not found'], 404);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error deleting promotion branch: ' . $e->getMessage());
        return response()->json(['message' => 'Error deleting promotion branch'], 500);
    }
}



public function restore($uuid)
{
    DB::beginTransaction();
    try {
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Buscar la promoción eliminada con el UUID proporcionado
        $promotionBranch = PromotionBranch::where('promotion_branch_uuid', $uuid)->onlyTrashed()->firstOrFail();

        // Restaurar la promoción eliminada
        $promotionBranch->restore();

        //Cache
        $this->refreshCache( "branch_promotion_{$uuid}", $this->cacheTime, function () use ($promotionBranch) {
        return $promotionBranch;
        });

        $this->updatePromotionCache($this->getAllPromotions($this->userId));

        DB::commit();

        return response()->json(new PromotionBranchResource($promotionBranch), 200);
    } catch (ModelNotFoundException $e) {
        DB::rollBack();
        return response()->json(['message' => 'Promotion branch not found in trash'], 404);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error occurred while restoring Promotion Branch: ' . $e->getMessage());
        return response()->json(['message' => 'Error occurred while restoring Promotion Branch'], 500);
    }
}


private function updatePromotionCache($allPromotions)
    {
        $this->refreshCache($this->cacheKey, $this->cacheTime, function () use ($allPromotions) {
            return $allPromotions;
        });
    }


}
