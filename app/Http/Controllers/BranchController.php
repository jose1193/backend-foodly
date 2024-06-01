<?php

namespace App\Http\Controllers;
use App\Http\Controllers\BaseController as BaseController;
use App\Models\BusinessBranch;
use App\Http\Requests\BranchRequest;
use App\Http\Resources\BranchResource;
use App\Models\BranchCoverImage;
use App\Models\Business;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\UpdateBranchLogoRequest;
use Illuminate\Support\Facades\Log;
use App\Helpers\ImageHelper;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\Jobs\SendWelcomeEmailBranch;

class BranchController extends BaseController
{
    protected $cacheKey;
    
    protected $cacheTime = 720;
    protected $userId;


    public function __construct()
{
   $this->middleware('check.permission:Manager')->only(['index','create', 'store', 'update', 'destroy','updateLogo']);

     $this->middleware(function ($request, $next) {
            $this->userId = Auth::id();
            $this->cacheKey = 'user_' . $this->userId . '_business_branches';
            return $next($request);
        });

}

    /**
     * Display a listing of the resource.
     */
    public function index()
{
    try {
        $businessBranches = $this->getCachedData($this->cacheKey, $this->cacheTime, function () {
            return $this->updateBranchCache();
        });

        return response()->json(['business_branches' => BranchResource::collection($businessBranches)], 200);
    } catch (\Exception $e) {
        if ($e->getMessage() == 'User has no associated businesses') {
            return response()->json(['message' => $e->getMessage()], 404);
        } elseif ($e->getMessage() == 'No business branches found') {
            return response()->json(['message' => $e->getMessage()], 404);
        } else {
            Log::error('Error retrieving business branches: ' . $e->getMessage());
            return response()->json(['error' => 'Error retrieving business branches'], 500);
        }
    }
}


private function updateBranchCache()
{
   
    $businesses = auth()->user()->businesses()->pluck('id');

    if ($businesses->isEmpty()) {
        throw new \Exception('User has no associated businesses');
    }

    $businessBranches = BusinessBranch::withTrashed()
        ->whereIn('business_id', $businesses)
        ->orderByDesc('id')
        ->get();

    if ($businessBranches->isEmpty()) {
        throw new \Exception('No business branches found');
    }

    $this->refreshCache($this->cacheKey, $this->cacheTime, function () use ($businessBranches) {
        return $businessBranches;
    });

    return $businessBranches;
}


    /**
     * Store a newly created resource in storage.
     */

    
public function store(BranchRequest $request)
{
    DB::beginTransaction();

    try {
        
        $data = $request->validated();
        $businessId = $request->input('business_id');
       
        $business =  auth()->user()->businesses()->find($businessId);

        if (!$business) {
            return response()->json(['message' => 'Unauthorized. Business does not belong to authenticated user.'], 401);
        }

        $data['branch_uuid'] = Uuid::uuid4()->toString();
        $data['branch_logo'] = $this->handleBranchLogo($request);
        $data['business_id'] = $businessId;

        $businessBranch = BusinessBranch::create($data);

        // Verificar y ajustar el formato de branch_services
            $services = $data['branch_services'] ?? [];
            if (!is_array($services)) {
                $services = [$services];  // Convertir a array si no lo es
            }

        // Asociar servicios con el negocio usando la tabla pivot
        $businessBranch->BranchServices()->attach($services);

        SendWelcomeEmailBranch::dispatch(auth()->user(), $businessBranch);

        $this->refreshCache('branch_' . $businessBranch->branch_uuid, $this->cacheTime, function () use ($businessBranch) {
        return $businessBranch;
        });

        $this->updateBranchCache();

        DB::commit();

        return response()->json(new BranchResource($businessBranch), 200);
    } catch (\Exception $e) {
        DB::rollback();
        Log::error('Error storing branch: ' . $e->getMessage());
        return response()->json(['error' => 'An error occurred while storing the branch'], 500);
    }
}


private function handleBranchLogo($request)
{
    if ($request->hasFile('branch_logo')) {
        return ImageHelper::storeAndResize($request->file('branch_logo'), 'public/branch_logos');
    }
    return null; // Retornar null si no hay logo para mantener la coherencia de los datos
}



public function updateLogo(Request $request, $uuid)
{
    try {
        $cacheKey = 'branch_' . $uuid;

        $business_branch = BusinessBranch::where('branch_uuid', $uuid)->firstOrFail();

        if ($request->hasFile('branch_logo')) {
            $image = $request->file('branch_logo');

             if ($business_branch->branch_logo) {
                ImageHelper::deleteFileFromStorage($business_branch->branch_logo);
                }

            $photoPath = ImageHelper::storeAndResize($image, 'public/branch_logos');

            $business_branch->branch_logo = $photoPath;
        }

        $business_branch->save();
        // Cache handling
        $this->refreshCache($cacheKey, $this->cacheTime, function () use ($business_branch) {
        return $business_branch;
        });

        $this->updateBranchCache();
       

        return response()->json(new BranchResource($business_branch), 200);
    } catch (ModelNotFoundException $e) {
        return response()->json(['error' => 'Business branch not found'], 404);
    } catch (\Exception $e) {
        Log::error('Error updating business branch logo image: ' . $e->getMessage());
        return response()->json(['error' => 'Error updating business branch logo image'], 500);
    }
}


    /**
     * Display the specified resource.
     */
 public function show(string $uuid)
{
    try {
        $branchCacheKey = 'branch_' . $uuid;

        // Obtener la sucursal del negocio por su UUID y almacenarla en caché si es necesario
        $business_branch = $this->getCachedData($branchCacheKey, $this->cacheTime, function () use ($uuid) {
            return BusinessBranch::withTrashed()->where('branch_uuid', $uuid)->firstOrFail();
        });

        // Devolver una respuesta JSON con la sucursal del negocio
        return response()->json(new BranchResource($business_branch), 200);
    } catch (ModelNotFoundException $e) {
        return response()->json(['message' => 'Business Branch not found'], 404);
    } catch (\Exception $e) {
        // Manejar cualquier excepción que ocurra durante el proceso
        return response()->json(['message' => 'Error retrieving Business Branch'], 500);
    }
}


    /**
     * Update the specified resource in storage.
     */
   public function update(BranchRequest $request, $uuid)
{
    try {
        return DB::transaction(function () use ($request, $uuid) {
            $cacheKey = "branch_{$uuid}";

            // Intentar obtener los datos de la caché, y si no están, obtenerlos de la base de datos
            $business_branch = $this->getCachedData($cacheKey, $this->cacheTime, function () use ($uuid) {
                $user = auth()->user();
                return BusinessBranch::where('branch_uuid', $uuid)
                    ->whereHas('business', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    })->firstOrFail();
            });

            // Actualizar la sucursal con los datos validados
            $business_branch->update($request->validated());

             if ($request->filled('branch_services')) {
                $serviceIds = $request->input('branch_services');
                if (!is_array($serviceIds)) {
                    $serviceIds = [$serviceIds];
                }
                $business_branch->BranchServices()->sync($serviceIds);
            }

           
            // Cache handling
        $this->refreshCache($cacheKey, $this->cacheTime, function () use ($business_branch) {
        return $business_branch;
        });

        $this->updateBranchCache();

            // Devolver una respuesta JSON con la sucursal actualizada
            return response()->json(new BranchResource($business_branch), 200);
        });
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // La sucursal no fue encontrada o no pertenece al usuario
        return response()->json(['message' => 'Business Branch not found or access denied'], 404);
    } catch (\Exception $e) {
        // Manejar cualquier otro error y registrar el mensaje de error
        Log::error('Error updating business branch: ' . $e->getMessage());
        return response()->json(['error' => 'Error updating business branch'], 500);
    }
}




    /**
     * Remove the specified resource from storage.
     */
    
public function destroy($uuid)
{
    try {
       
        $businessBranch = $this->getCachedData("branch_{$uuid}", $this->cacheTime, function () use ($uuid) {
            return BusinessBranch::where('branch_uuid', $uuid)->firstOrFail();
        });

        // Invalidar el caché de la sucursal de negocio
        $this->invalidateCache("branch_{$uuid}");
        $this->updateBranchCache();

        // Marcar la sucursal de negocio como eliminada (soft delete)
        $businessBranch->delete();

        return response()->json(['message' => 'Business Branch deleted successfully'], 200);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
       
        return response()->json(['message' => 'Business Branch not found'], 404);
    } catch (\Exception $e) {
        
        Log::error('Error occurred while deleting Business Branch: ' . $e->getMessage());
        return response()->json(['message' => 'Error occurred while deleting Business Branch'], 500);
    }
}

public function restore($uuid)
{
    DB::beginTransaction();
    
    try {
        // Buscar la sucursal de negocio eliminada con el UUID proporcionado
        $businessBranch = BusinessBranch::where('branch_uuid', $uuid)->onlyTrashed()->first();

        if (!$businessBranch) {
            return response()->json(['message' => 'Business Branch not found in trash or unauthorized'], 404);
        }

        if (!$businessBranch->trashed()) {
            return response()->json(['message' => 'Business Branch already restored'], 400);
        }

        // Restaurar la sucursal de negocio
        $businessBranch->restore();
        
       // Cache handling
        $this->refreshCache("branch_{$uuid}", $this->cacheTime, function () use ($businessBranch) {
        return $businessBranch;
        });

        $this->updateBranchCache();

        DB::commit();

       
        return response()->json(new BranchResource($businessBranch), 200);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        DB::rollBack();
       
        return response()->json(['message' => 'Business Branch not found in trash'], 404);
    } catch (\Exception $e) {
        DB::rollBack();
       
        Log::error('Error occurred while restoring Business Branch: ' . $e->getMessage());
        return response()->json(['message' => 'Error occurred while restoring Business Branch'], 500);
    }
}





}