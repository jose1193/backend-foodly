<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Subcategory;
use App\Http\Requests\SubcategoryRequest;
use App\Http\Resources\SubcategoryResource;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Routing\Controller as BaseController;


class SubcategoryController extends BaseController
{
     // PERMISSIONS USERS
    public function __construct()
{
   $this->middleware('check.permission:Super Admin')->only(['store', 'edit', 'update', 'destroy']);

}
    

   public function index()
{
    try {
        $cacheKey = 'subcategories';

        // Attempt to retrieve cached data
        $subcategories = $this->getCachedData($cacheKey, 60, function () {
            return Subcategory::orderBy('id', 'desc')->get();
        });

        return response()->json(['subcategories' => SubcategoryResource::collection($subcategories)]);
    } catch (\Exception $e) {
        Log::error('Failed to retrieve subcategories: ' . $e->getMessage());
        return response()->json(['message' => 'Failed to retrieve subcategories'], 500);
    }
}

public function store(SubcategoryRequest $request)
{
    DB::beginTransaction();
    try {
        $validatedData = $request->validated();
        $validatedData['subcategory_uuid'] = Uuid::uuid4()->toString();
        
        $subcategory = Subcategory::create($validatedData);
        
        // Commit the transaction
        DB::commit();
        
        // Update the cache
        $cacheKey = 'subcategories';
        $this->invalidateCache($cacheKey);
        $this->getCachedData($cacheKey, 60, function () {
            return Subcategory::orderBy('id', 'desc')->get();
        });

        return response()->json(new SubcategoryResource($subcategory), 201);
    } catch (\Exception $e) {
        // Rollback the transaction in case of error
        DB::rollBack();
        
        Log::error('Error creating subcategory: ' . $e->getMessage());
        
        return response()->json(['message' => 'Error creating subcategory'], 500);
    }
}




   public function show($uuid)
{
    try {
        // Definir la clave de caché
        $cacheKey = 'subcategory_' . $uuid;

        // Intentar obtener la subcategoría desde la caché
        $subcategory = $this->getCachedData($cacheKey, 60, function () use ($uuid) {
            // Buscar la subcategoría por su UUID
            return Subcategory::where('subcategory_uuid', $uuid)->firstOrFail();
        });

        // Devolver una respuesta JSON con la subcategoría encontrada
        return response()->json(new SubcategoryResource($subcategory), 200);
    } catch (ModelNotFoundException $e) {
        // Manejar el caso en que la subcategoría no se encuentre
        return response()->json(['message' => 'Subcategory not found'], 404);
    } catch (\Exception $e) {
        // Manejar cualquier otro error
        Log::error('Error retrieving subcategory: ' . $e->getMessage());
        return response()->json(['message' => 'Error retrieving subcategory'], 500);
    }
}





 public function update(SubcategoryRequest $request, $uuid)
{
    DB::beginTransaction();
    try {
        // Encontrar la subcategoría por su UUID
        $subcategory = Subcategory::where('subcategory_uuid', $uuid)->firstOrFail();

        // Actualizar la subcategoría con los datos validados de la solicitud
        $subcategory->update($request->validated());

        // Commit de la transacción
        DB::commit();

        // Invalidar la caché de la subcategoría
        $cacheKey = 'subcategory_' . $uuid;
        $this->invalidateCache($cacheKey);
        $this->getCachedData($cacheKey, 60, function () use ($uuid) {
            return Subcategory::where('subcategory_uuid', $uuid)->firstOrFail();
        });

        // Devolver una respuesta JSON con la subcategoría actualizada
        return response()->json(new SubcategoryResource($subcategory), 200);
    } catch (ModelNotFoundException $e) {
        // Rollback de la transacción en caso de que la subcategoría no se encuentre
        DB::rollBack();
        return response()->json(['message' => 'Subcategory not found'], 404);
    } catch (\Exception $e) {
        // Rollback de la transacción en caso de cualquier otra excepción
        DB::rollBack();
        Log::error('Error updating subcategory: ' . $e->getMessage());
        return response()->json(['message' => 'Error updating subcategory'], 500);
    }
}


public function destroy($uuid)
{
    DB::beginTransaction();
    try {
        // Encontrar la subcategoría por su UUID
        $subcategory = Subcategory::where('subcategory_uuid', $uuid)->first();

        // Verificar si se encontró la subcategoría
        if (!$subcategory) {
            // Rollback de la transacción si no se encuentra la subcategoría
            DB::rollBack();
            return response()->json(['message' => 'Subcategory not found'], 404);
        }

        // Eliminar la subcategoría
        $subcategory->delete();

        // Commit de la transacción
        DB::commit();

        // Invalidar la caché de la subcategoría
        $cacheKey = 'subcategory_' . $uuid;
        $this->invalidateCache($cacheKey);

        // Devolver una respuesta JSON con un mensaje de éxito
        return response()->json(['message' => 'Subcategory successfully removed'], 200);
    } catch (\Exception $e) {
        // Rollback de la transacción en caso de cualquier otra excepción
        DB::rollBack();
        Log::error('Error deleting subcategory: ' . $e->getMessage());
        return response()->json(['message' => 'Error deleting subcategory'], 500);
    }
}



private function getCachedData($key, $minutes, \Closure $callback)
{
    return Cache::remember($key, now()->addMinutes($minutes), $callback);
}

private function putCachedData($key, $data, $minutes)
{
    Cache::put($key, $data, now()->addMinutes($minutes));
}

private function invalidateCache($key)
{
    Cache::forget($key);
}



}
