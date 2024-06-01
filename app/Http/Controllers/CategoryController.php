<?php

namespace App\Http\Controllers;
use App\Http\Controllers\BaseController as BaseController;
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


class CategoryController extends BaseController


{


use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    

    protected $cacheKey;
    
    protected $cacheTime = 720;
    protected $userId;


    public function __construct()
{
  $this->middleware('check.permission:Super Admin')->only([
            'create', 'store', 'edit', 'update', 'destroy', 'updateImage', 'show'
        ]);

  $this->middleware(function ($request, $next) {
            $this->userId = Auth::id();
            $this->cacheKey = 'categories';
            return $next($request);
        });

}
    

  public function index()
{
    try {
        // Obtener las categorías del caché o de la base de datos
        $categories = $this->getCachedData($this->cacheKey, $this->cacheTime, function () {
            return $this->retrieveCategories();
        });

        return response()->json([
            'categories' => CategoryResource::collection($categories)
        ], 200);
    } catch (\Exception $e) {
        Log::error('Error retrieving categories: ' . $e->getMessage());
        return response()->json(['error' => 'An error occurred while retrieving categories'], 500);
    }
}

private function retrieveCategories()
{
    return Category::orderBy('id', 'desc')->get();
}

// Actualización específica de caché 
private function updateCategoriesCache()
{
    $this->refreshCache( $this->cacheKey, $this->cacheTime, function () {
        return $this->retrieveCategories();
    });
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

       
        
        $this->updateCategoriesCache();

        return $this->successfulResponse($category);
    } catch (\Throwable $e) {
        Log::error('An error occurred while creating category: ' . $e->getMessage(), [
            'request' => $request->all(),
            'validatedData' => $validatedData,
        ]);

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
            $cacheKey = "category_{$uuid}";

            // Obtener la categoría desde el caché o la base de datos
            $category = $this->getCachedData($cacheKey, 720, function () use ($uuid) {
                return Category::where('category_uuid', $uuid)->firstOrFail();
            });

           
            if ($request->hasFile('category_image_path')) {
                // Obtener el archivo de imagen
                $image = $request->file('category_image_path');

               
                if ($category->category_image_path) {
                    ImageHelper::deleteFileFromStorage($category->category_image_path);
                }

                // Guardar la nueva imagen y obtener la ruta
                $photoPath = ImageHelper::storeAndResize($image, 'public/categories_images');

                // Actualizar la ruta de la imagen en el modelo Category
                $category->category_image_path = $photoPath;
                $category->save();

                
           
             // Actualiza la caché
            $this->refreshCache($this->cacheKey, $this->cacheTime, function () use ($category) {
                    return $category;
                });

            $this->updateCategoriesCache();

            }

           
            return response()->json(new CategoryResource($category), 200);
        });
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        Log::warning("Category with UUID {$uuid} not found");
        return response()->json(['error' => 'Category not found'], 404);
    } catch (\Exception $e) {
       
        Log::error('Error updating category image: ' . $e->getMessage());
        return response()->json(['error' => 'Error updating category image'], 500);
    }
}




public function show($uuid)
{
    try {
        $cacheKey = "category_{$uuid}";
       

        // Obtener la categoría desde el caché o la base de datos
        $category = $this->getCachedData($cacheKey, $this->cacheTime, function () use ($uuid) {
            return Category::where('category_uuid', $uuid)->firstOrFail();
        });

        
        return response()->json(new CategoryResource($category), 200);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
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
       
        $cacheKey = "category_{$uuid}";
        $cacheTime = 720; 
       
        $category = $this->getCachedData($cacheKey, $cacheTime, function () use ($uuid) {
            return Category::where('category_uuid', $uuid)->firstOrFail();
        });

       
        return DB::transaction(function () use ($request, $category, $cacheKey, $cacheTime) {
           
            $category->update($request->validated());

       
        // Actualiza la caché
        $this->refreshCache($cacheKey, 720, function () use ($category) {
        return $category;
        });

        $this->updateCategoriesCache();

           
            return response()->json(new CategoryResource($category), 200);
        });
    } catch (ModelNotFoundException $e) {
        return response()->json(['message' => 'Category not found'], 404);
    } catch (\Exception $e) {
       
        Log::error('Error updating category: ' . $e->getMessage());
        return response()->json(['message' => 'Error updating category'], 500);
    }
}


public function destroy($uuid)
{
    try {
       
        $cacheKey = "category_{$uuid}";
        

        // Obtener la categoría desde el caché o la base de datos
        $category = $this->getCachedData($cacheKey, $this->cacheTime, function () use ($uuid) {
            return Category::where('category_uuid', $uuid)->firstOrFail();
        });

       
        if ($category->category_image_path) {
            ImageHelper::deleteFileFromStorage($category->category_image_path);
        }

      
        $category->delete();

      
        // Actualiza la caché
         $this->invalidateCache($cacheKey);

        $this->updateCategoriesCache();

      
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
