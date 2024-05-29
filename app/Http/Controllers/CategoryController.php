<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Category;
use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManagerStatic as Image;
use Ramsey\Uuid\Uuid;
use App\Http\Requests\UpdateCategoryImageRequest;
use Illuminate\Support\Facades\Log;
use App\Helpers\ImageHelper;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;


use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Cache;

use Illuminate\Routing\Controller as BaseController;


class CategoryController extends BaseController


{


use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    // PERMISSIONS USERS
    public function __construct()
{
  $this->middleware('check.permission:Super Admin')->only([
            'create', 'store', 'edit', 'update', 'destroy', 'updateImage', 'show'
        ]);

}
    

    public function index()
{
    try {
        // Definir la clave de caché y el tiempo de caché
        $cacheKey = 'categories';
        $cacheTime = config('cache.times.categories', 43200); // Configurable desde los archivos de configuración

        // Cachear las categorías durante el tiempo definido
        $categories = Cache::remember($cacheKey, $cacheTime, function () {
            return Category::orderBy('id', 'desc')->get();
        });

        // Retornar la respuesta JSON usando CategoryResource
        return response()->json([
            'categories' => CategoryResource::collection($categories)
        ], 200);
    } catch (\Exception $e) {
        // Manejar errores y registrar el error
        Log::error('Error retrieving categories: ' . $e->getMessage());
        return response()->json(['error' => 'An error occurred while retrieving categories'], 500);
    }
}


public function store(CategoryRequest $request)
{
    $validatedData = $this->prepareData($request);

    try {
        $category = DB::transaction(function () use ($validatedData, $request) {
            $category = $this->createCategory($validatedData);
            $this->handleCategoryImage($request, $category);
            return $category;
        });

        // Actualizar caché
        $this->updateCategoriesCache();

        return $this->successfulResponse($category);
    } catch (\Throwable $e) {
        Log::error('An error occurred while creating category: ' . $e->getMessage());
        return $this->errorResponse();
    }
}

private function prepareData($request)
{
    return array_merge($request->validated(), [
        'user_id' => Auth::id(),
        'category_uuid' => Uuid::uuid4()->toString()
    ]);
}

private function createCategory($data)
{
    return Category::create($data);
}

private function handleCategoryImage($request, $category)
{
    if ($request->hasFile('category_image_path')) {
        $imagePath = ImageHelper::storeAndResize($request->file('category_image_path'), 'public/categories_images');
        $category->category_image_path = $imagePath;
        $category->save();
    }
}

private function updateCategoriesCache()
{
    $cacheKey = 'categories';
    $cacheTime = config('cache.times.categories', 43200); // Asegurarse de usar el mismo tiempo de caché

    Cache::forget($cacheKey); // Eliminar el caché existente
    Cache::remember($cacheKey, now()->addSeconds($cacheTime), function () {
        return Category::orderBy('id', 'desc')->get();
    });
}


private function successfulResponse($category)
{
    return response()->json(new CategoryResource($category), 200);
}

private function errorResponse()
{
    return response()->json(['error' => 'An error occurred while creating category'], 500);
}


public function updateImage(UpdateCategoryImageRequest $request, $uuid)
{
    try {
        return DB::transaction(function () use ($request, $uuid) {
            $category = Category::where('category_uuid', $uuid)->firstOrFail();

            // Guardar la imagen si está presente
            if ($request->hasFile('category_image_path')) {
                // Obtener el archivo de imagen
                $image = $request->file('category_image_path');

                // Eliminar la imagen anterior si existe
                   if ($category->category_image_path) {
                    ImageHelper::deleteFileFromStorage($category->category_image_path);
                    }


                // Guardar la nueva imagen y obtener la ruta
                $photoPath = ImageHelper::storeAndResize($image, 'public/categories_images');

                // Actualizar la ruta de la imagen en el modelo Category
                $category->category_image_path = $photoPath;
                $category->save();

                // Actualizar la entrada de caché con la categoría actualizada
                $this->updateCategoryCache($category);
            }

            // Devolver el recurso actualizado
            return response()->json(new CategoryResource($category), 200);
        });
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        Log::warning("Category with UUID {$uuid} not found");
        return response()->json(['error' => 'Category not found'], 404);
    } catch (\Exception $e) {
        // Manejar el error y registrar el mensaje de error si es necesario
        Log::error('Error updating category image: ' . $e->getMessage());
        return response()->json(['error' => 'Error updating category image'], 500);
    }
}

private function updateCategoryCache($category)
{
    $cacheKey = 'category_' . $category->category_uuid;
    $cacheTime = config('cache.times.category', 43200); // Tiempo de caché en segundos
    $cacheMinutes = $cacheTime / 60; // Convertir segundos a minutos

    Cache::forget($cacheKey); // Eliminar el caché existente
    Cache::put($cacheKey, $category->fresh(), now()->addMinutes($cacheMinutes));
}



public function show($uuid)
{
    try {
        // Definir la clave de caché y el tiempo de caché
        $cacheKey = "category_{$uuid}";
        $cacheTime = config('cache.times.category', 43200); // Configurable desde los archivos de configuración

        // Intentar obtener la categoría del caché, si no está en el caché, recuperarla de la base de datos
        $category = Cache::remember($cacheKey, $cacheTime, function () use ($uuid) {
            return Category::where('category_uuid', $uuid)->firstOrFail();
        });

        // Devolver una respuesta JSON con la categoría encontrada
        return response()->json(new CategoryResource($category), 200);
    } catch (ModelNotFoundException $e) {
        // Manejar el caso en que la categoría no se encuentre y registrar el error
        Log::warning("Category with UUID {$uuid} not found");
        return response()->json(['message' => 'Category not found'], 404);
    } catch (\Exception $e) {
        // Manejar cualquier otro error y registrar el mensaje de error
        Log::error('Error retrieving category: ' . $e->getMessage());
        return response()->json(['message' => 'An error occurred while retrieving the category'], 500);
    }
}





public function update(CategoryRequest $request, $uuid)
{
    try {
        // Encontrar la categoría por su UUID
        $category = Category::where('category_uuid', $uuid)->firstOrFail();

        // Iniciar la transacción
        return DB::transaction(function () use ($request, $category) {
            // Actualizar la categoría con los datos validados de la solicitud
            $category->update($request->validated());

            // Eliminar la caché de la lista de categorías para asegurar consistencia
            Cache::forget('categories');

            // Actualizar la caché de la categoría específica
            $this->updateCategoryCache($category);

            // Devolver una respuesta JSON con la categoría actualizada
            return response()->json(new CategoryResource($category), 200);
        });
    } catch (ModelNotFoundException $e) {
        return response()->json(['message' => 'Category not found'], 404);
    } catch (\Exception $e) {
        // Manejar cualquier excepción y devolver una respuesta de error
        Log::error('Error updating category: ' . $e->getMessage());
        return response()->json(['message' => 'Error updating category'], 500);
    }
}


public function destroy($uuid)
    {
        try {
            // Encontrar la categoría por su UUID o lanzar una excepción si no se encuentra
            $category = Category::where('category_uuid', $uuid)->firstOrFail();

            // Eliminar las imágenes asociadas si existen
                if ($category->category_image_path) {
                ImageHelper::deleteFileFromStorage($category->category_image_path);
                }
            // Eliminar la categoría
            $category->delete();

            // Eliminar el caché
            Cache::forget('categories');
             Cache::forget("category_{$uuid}");

            // Devolver una respuesta JSON con un mensaje de éxito
            return response()->json(['message' => 'Category successfully removed'], 200);
        } catch (ModelNotFoundException $e) {
            // Manejar el caso donde la categoría no fue encontrada
            return response()->json(['message' => 'Category not found'], 404);
        } catch (\Exception $e) {
            // Manejar cualquier otro error y registrar el mensaje de error
            Log::error('An error occurred while removing the category: ' . $e->getMessage());
            return response()->json(['error' => 'An error occurred while removing the category'], 500);
        }
    }


}
