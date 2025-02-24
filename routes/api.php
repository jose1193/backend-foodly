<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


use App\Http\Controllers\AuthController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\PermissionController;
//use App\Http\Controllers\Api\ErrorController;
use Illuminate\Support\Facades\Response;
use App\Http\Controllers\ProfilePhotoController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SubcategoryController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\BusinessCoverImageController;
use App\Http\Controllers\CheckUsernameController;
use App\Http\Controllers\CheckEmailController;
use App\Http\Controllers\BranchController;
use App\Http\Controllers\BranchCoverImageController;
use App\Http\Controllers\BiometricAuthController;
use App\Http\Controllers\PromotionController;
use App\Http\Controllers\PromotionMediaController;
use App\Http\Controllers\PromotionBranchController;
use App\Http\Controllers\PromotionBranchImageController;
use App\Http\Controllers\SocialLoginController;
use App\Http\Controllers\TwitterLoginController;
use App\Http\Controllers\CreateUserController;
use App\Http\Controllers\PasswordResetUserController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TwitterController;
use App\Http\Controllers\BusinessMenuController;
use App\Http\Controllers\BusinessFoodCategoryController;
use App\Http\Controllers\BusinessDrinkCategoryController;
use App\Http\Controllers\BusinessComboController;
use App\Http\Controllers\BusinessComboPhotosController;
use App\Http\Controllers\BusinessFoodItemController;
use App\Http\Controllers\BusinessDrinkItemController;
use App\Http\Controllers\BusinessFoodItemPhotoController;
use App\Http\Controllers\BusinessDrinkItemPhotoController;
use App\Http\Controllers\HaversineSearchController;
use App\Http\Controllers\BusinessFavoriteController;
use App\Http\Controllers\BusinessMenuFavoriteController;
use App\Http\Controllers\BusinessFoodItemFavoriteController;
use App\Http\Controllers\BusinessDrinkItemFavoriteController;
use App\Http\Controllers\SavePromotionController;
use App\Http\Controllers\UserFollowerController;

//Route::get('/user', function (Request $request) {
    //return $request->user();
//})->middleware('auth:sanctum');

Route::post('login', [AuthController::class, 'login']);

Route::post('/register', [CreateUserController::class, 'store']);

Route::get('/username-available/{username}', [CheckUsernameController::class, 'checkUsernameAvailability']);

Route::get('/email-available/{email}', [CheckEmailController::class, 'checkEmailAvailability']);

Route::get('/categories', [CategoryController::class, 'index']);

// Route related to User Social Login
Route::post('/social-login', [SocialLoginController::class, 'handleProviderCallback']);

Route::post('/auth/twitter', [TwitterLoginController::class, 'handleProvider']);  
Route::get('/auth/twitter/callback', [TwitterLoginController::class, 'handleTwitterCallback']);

// Public route to list menus of all businesses
Route::get('/public', [BusinessMenuController::class, 'publicIndex']);

Route::controller(PasswordResetUserController::class)->group(function () {
    Route::post('/forgot-password', 'store'); 
    Route::post('/enter-pin', 'verifyResetPassword');
    Route::post('/reset-password', 'updatePassword');  
   
});

Route::get('/services', [ServiceController::class, 'index']);

Route::get('business-menu/{uuid}', [BusinessMenuController::class, 'show']);
//Route::controller(RegisterController::class)->group(function(){
    //Route::post('register', 'register');
    //Route::post('login', 'login');
//});

Route::get('auth/twitter', [TwitterController::class, 'redirectToTwitter']);
Route::post('twitter/callback', [TwitterController::class, 'handleTwitterCallback']);
Route::post('/twitter/user-details', [TwitterController::class, 'getUserDetails']);


Route::post('/business/search', [HaversineSearchController::class, 'search']);

Route::middleware(['auth:sanctum','handle.notfound'])->group(function() {
    //Route::get('/user', function (Request $request) {
        //return $request->user();
    //});

    // Rutas protegidas por autenticación y verificación
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'user']);
    //Route::get('/users', [AuthController::class, 'getUsers']);
    Route::post('update-password', [AuthController::class, 'updatePassword']);
    
    //Route::post('reset-password', [AuthController::class, 'resetPassword']);
    Route::post('update-profile', [CreateUserController::class, 'update']);
    Route::post('update-profile-photo', [ProfilePhotoController::class, 'update']);
    
    Route::get('/user/{uuid}', [AuthController::class, 'getUserByUuid']);

    // Rutas relacionadas con roles
    
    Route::get('roles', [RoleController::class, 'index']); // Obtener una lista de roles
    Route::post('roles/store', [RoleController::class, 'store']); // Crear un nuevo rol
    Route::get('roles/{id}', [RoleController::class, 'show']); // Mostrar un rol específico
    Route::put('roles-update/{id}', [RoleController::class, 'update']); // Actualizar un rol existente
    Route::delete('roles-delete/{id}', [RoleController::class, 'destroy']); // Eliminar un rol existente
    Route::get('roles-permissions', [RoleController::class, 'create']); // Mostrar listado de permisos
    Route::get('roles/{id}/edit', [RoleController::class, 'edit']); // Mostrar listado de roles y permisos del usuario a editar

    // Rutas relacionadas con usuarios
    Route::get('users-list', [UsersController::class, 'index']); 
    Route::post('users-store', [UsersController::class, 'store']); 
    Route::get('users-profile/{uuid}', [UsersController::class, 'show']); 
    Route::put('users-update/{uuid}', [UsersController::class, 'update']); 
    Route::delete('users-delete/{id}', [UsersController::class, 'destroy']); 
    Route::get('users-create', [UsersController::class, 'create']); 
    Route::get('users-list/{uuid}/edit', [UsersController::class, 'edit']); 
    Route::put('users-restore/{uuid}', [UsersController::class, 'restore']); 

    // Rutas relacionadas con permisos
    Route::get('permissions-list', [PermissionController::class, 'index']);
    Route::post('permissions', [PermissionController::class, 'store']);
    Route::get('permissions/{id}', [PermissionController::class, 'show']);
    Route::put('permissions-update/{id}', [PermissionController::class, 'update']);
    Route::delete('permissions-delete/{id}', [PermissionController::class, 'destroy']);
    Route::get('permissions/create', [PermissionController::class, 'create']);
    Route::get('permissions/{id}/edit', [PermissionController::class, 'edit']);

    // Routes related to Categories
    

    Route::post('/categories-store', [CategoryController::class, 'store']);
    Route::put('/categories-update/{uuid}', [CategoryController::class, 'update']);
    Route::get('/categories/{uuid}', [CategoryController::class, 'show']);
    Route::delete('/categories-delete/{uuid}', [CategoryController::class, 'destroy']);
    Route::post('/categories-update-images/{uuid}/', [CategoryController::class, 'updateImage']);

     // Routes related to Subcategories
    Route::get('/subcategories', [SubcategoryController::class, 'index']);
    Route::post('/subcategories-store', [SubcategoryController::class, 'store']);
    Route::put('/subcategories-update/{uuid}', [SubcategoryController::class, 'update']);
    Route::get('/subcategories/{uuid}', [SubcategoryController::class, 'show']);
    Route::delete('/subcategories-delete/{uuid}', [SubcategoryController::class, 'destroy']);

    // Routes related to Business
    Route::get('/business', [BusinessController::class, 'index']);
    Route::post('/business-store', [BusinessController::class, 'store']);
    Route::put('/business-update/{uuid}', [BusinessController::class, 'update']);
    Route::get('/business/{uuid}', [BusinessController::class, 'show']);
    Route::delete('/business-delete/{uuid}', [BusinessController::class, 'destroy']);
    Route::post('/business-update-logo/{uuid}', [BusinessController::class, 'updateLogo']);
    Route::put('/business-restore/{uuid}', [BusinessController::class, 'restore']);


    // Routes related to Business Cover Images
    Route::get('/business-cover-images', [BusinessCoverImageController::class, 'index']);
    Route::post('/business-cover-images-store', [BusinessCoverImageController::class, 'store']);
    Route::get('/business-cover-images/{uuid}', [BusinessCoverImageController::class, 'show']);
    //Route::put('/business-cover-images/{cover_image_uuid}', [BusinessCoverImageController::class, 'update']);
    Route::delete('/business-cover-images-delete/{uuid}', [BusinessCoverImageController::class, 'destroy']);
    Route::post('/business-cover-images-update/{uuid}', [BusinessCoverImageController::class, 'updateImage']);
    
        // Routes related to Business
    Route::get('/branch', [BranchController::class, 'index']);
    Route::post('/branch-store', [BranchController::class, 'store']);
    Route::put('/branch-update/{uuid}', [BranchController::class, 'update']);
    Route::get('/branch/{uuid}', [BranchController::class, 'show']);
    Route::post('/branch-update-logo/{uuid}', [BranchController::class, 'updateLogo']);
    Route::delete('/branch-delete/{uuid}', [BranchController::class, 'destroy']);
    Route::put('/branch-restore/{uuid}', [BranchController::class, 'restore']);


    // Routes related to Branch Cover Images
    Route::get('/branch-cover-images', [BranchCoverImageController::class, 'index']);
    Route::post('/branch-cover-images-store', [BranchCoverImageController::class, 'store']);
    Route::get('/branch-cover-images/{uuid}', [BranchCoverImageController::class, 'show']);
    Route::post('/branch-cover-images-update/{uuid}', [BranchCoverImageController::class, 'updateImage']);
    Route::delete('/branch-cover-images-delete/{uuid}', [BranchCoverImageController::class, 'destroy']);
     
   
    // Routes related to Biometric Login
    Route::post('/biometric-login', [BiometricAuthController::class, 'store']);

    // Routes related to Business Promotions

    Route::prefix('promotions')->group(function () {
    Route::get('/', [PromotionController::class, 'index']);    
    Route::post('/store', [PromotionController::class, 'store']); 
    Route::get('/{uuid}', [PromotionController::class, 'show']); 
    Route::patch('/update/{uuid}', [PromotionController::class, 'update']); 
    Route::delete('/delete/{uuid}', [PromotionController::class, 'destroy']);
    Route::put('/restore/{uuid}', [PromotionController::class, 'restore']);
   
    });

     // Routes related to Promotions Business Images

    Route::prefix('promotions-media')->group(function () {
    Route::get('/', [PromotionMediaController::class, 'index']);    
    Route::post('/store', [PromotionMediaController::class, 'store']); 
    Route::get('/{uuid}', [PromotionMediaController::class, 'show']); 
    Route::post('/update/{uuid}', [PromotionMediaController::class, 'update']); 
    Route::delete('/delete/{uuid}', [PromotionMediaController::class, 'destroy']);
   
   
    });


   
    // Routes related to Promotions Branch 
    Route::get('/branch-promotions', [PromotionBranchController::class, 'index']);
    Route::post('/branch-promotions-store', [PromotionBranchController::class, 'store']);
    Route::put('/branch-promotions-update/{uuid}', [PromotionBranchController::class, 'update']);
    Route::get('/branch-promotions/{uuid}', [PromotionBranchController::class, 'show']);
    Route::delete('/branch-promotions-delete/{uuid}', [PromotionBranchController::class, 'destroy']);
    Route::put('/branch-promotions-restore/{uuid}', [PromotionBranchController::class, 'restore']);

     // Routes related to Promotions Branches Images
    Route::get('/branch-promotions-images', [PromotionBranchImageController::class, 'index']);
    Route::post('/branch-promotions-images-store', [PromotionBranchImageController::class, 'store']);
    Route::get('/branch-promotions-images/{uuid}', [PromotionBranchImageController::class, 'show']);
    Route::delete('/branch-promotions-images-delete/{uuid}', [PromotionBranchImageController::class, 'destroy']);
    Route::post('/branch-promotions-images-update/{uuid}', [PromotionBranchImageController::class, 'updateImage']);
    
    // Routes related to Services
    Route::prefix('services')->group(function () {
    Route::post('/store', [ServiceController::class, 'store']); 
    Route::get('/{uuid}', [ServiceController::class, 'show']); 
    Route::patch('/update/{uuid}', [ServiceController::class, 'update']); 
    Route::post('/update-images/{uuid}/', [ServiceController::class, 'updateImage']); 
    Route::delete('delete/{uuid}', [ServiceController::class, 'destroy']); 
    });



Route::group(['prefix' => 'business-menu'], function () {
    Route::get('/', [BusinessMenuController::class, 'index']);
    Route::post('/store', [BusinessMenuController::class, 'store']);
   
    Route::patch('/update/{uuid}', [BusinessMenuController::class, 'update']);
    Route::delete('/delete/{uuid}', [BusinessMenuController::class, 'destroy']);
});

Route::group(['prefix' => 'business-food-categories'], function () {
    Route::get('/', [BusinessFoodCategoryController::class, 'index']);
    Route::post('/store', [BusinessFoodCategoryController::class, 'store']);
    Route::get('/{uuid}', [BusinessFoodCategoryController::class, 'show']);
    Route::patch('/update/{uuid}', [BusinessFoodCategoryController::class, 'update']);
    Route::delete('/delete/{uuid}', [BusinessFoodCategoryController::class, 'destroy']);
});

Route::group(['prefix' => 'business-drink-categories'], function () {
    Route::get('/', [BusinessDrinkCategoryController::class, 'index']);
    Route::post('/store', [BusinessDrinkCategoryController::class, 'store']);
    Route::get('/{uuid}', [BusinessDrinkCategoryController::class, 'show']);
    Route::patch('/update/{uuid}', [BusinessDrinkCategoryController::class, 'update']);
    Route::delete('/delete/{uuid}', [BusinessDrinkCategoryController::class, 'destroy']);
});


Route::group(['prefix' => 'business-combos'], function () {
    Route::get('/', [BusinessComboController::class, 'index']);
    Route::post('/store', [BusinessComboController::class, 'store']);
    Route::get('/{uuid}', [BusinessComboController::class, 'show']);
    Route::patch('/update/{uuid}', [BusinessComboController::class, 'update']);
    Route::delete('/delete/{uuid}', [BusinessComboController::class, 'destroy']);
});


Route::group(['prefix' => 'business-combos-photos'], function () {
    Route::get('/', [BusinessComboPhotosController::class, 'index']);
    Route::post('/store', [BusinessComboPhotosController::class, 'store']);
    Route::get('/{uuid}', [BusinessComboPhotosController::class, 'show']);
    Route::post('/update/{uuid}', [BusinessComboPhotosController::class, 'update']);
    Route::delete('/delete/{uuid}', [BusinessComboPhotosController::class, 'destroy']);
});


Route::group(['prefix' => 'business-food-item'], function () {
    Route::get('/', [BusinessFoodItemController::class, 'index']);
    Route::post('/store', [BusinessFoodItemController::class, 'store']);
    Route::get('/{uuid}', [BusinessFoodItemController::class, 'show']);
    Route::patch('/update/{uuid}', [BusinessFoodItemController::class, 'update']);
    Route::delete('/delete/{uuid}', [BusinessFoodItemController::class, 'destroy']);
});

Route::group(['prefix' => 'business-drink-item'], function () {
    Route::get('/', [BusinessDrinkItemController::class, 'index']);
    Route::post('/store', [BusinessDrinkItemController::class, 'store']);
    Route::get('/{uuid}', [BusinessDrinkItemController::class, 'show']);
    Route::patch('/update/{uuid}', [BusinessDrinkItemController::class, 'update']);
    Route::delete('/delete/{uuid}', [BusinessDrinkItemController::class, 'destroy']);
});

Route::group(['prefix' => 'business-food-item-photos'], function () {
    Route::get('/', [BusinessFoodItemPhotoController::class, 'index']);
    Route::post('/store', [BusinessFoodItemPhotoController::class, 'store']);
    Route::get('/{uuid}', [BusinessFoodItemPhotoController::class, 'show']);
    Route::post('/update/{uuid}', [BusinessFoodItemPhotoController::class, 'update']);
    Route::delete('/delete/{uuid}', [BusinessFoodItemPhotoController::class, 'destroy']);
});

Route::group(['prefix' => 'business-drink-item-photos'], function () {
    Route::get('/', [BusinessDrinkItemPhotoController::class, 'index']);
    Route::post('/store', [BusinessDrinkItemPhotoController::class, 'store']);
    Route::get('/{uuid}', [BusinessDrinkItemPhotoController::class, 'show']);
    Route::post('/update/{uuid}', [BusinessDrinkItemPhotoController::class, 'update']);
    Route::delete('/delete/{uuid}', [BusinessDrinkItemPhotoController::class, 'destroy']);
});

Route::group(['prefix' => 'business-favorites'], function () {
    Route::get('/', [BusinessFavoriteController::class, 'index']);
    Route::post('/{businessUuid}', [BusinessFavoriteController::class, 'toggle']);
    Route::get('/check/{businessUuid}', [BusinessFavoriteController::class, 'check']);
});

Route::group(['prefix' => 'business-menu-favorites'], function () {
    Route::get('/', [BusinessMenuFavoriteController::class, 'index']);
    Route::post('/{uuid}', [BusinessMenuFavoriteController::class, 'toggle']);
    Route::get('/check/{uuid}', [BusinessMenuFavoriteController::class, 'check']);
});

Route::group(['prefix' => 'business-food-item-favorites'], function () {
    Route::get('/', [BusinessFoodItemFavoriteController::class, 'index']);
    Route::post('/{uuid}', [BusinessFoodItemFavoriteController::class, 'toggle']);
    Route::get('/check/{uuid}', [BusinessFoodItemFavoriteController::class, 'check']);
});

Route::group(['prefix' => 'business-drink-item-favorites'], function () {
    Route::get('/', [BusinessDrinkItemFavoriteController::class, 'index']);
    Route::post('/{uuid}', [BusinessDrinkItemFavoriteController::class, 'toggle']);
    Route::get('/check/{uuid}', [BusinessDrinkItemFavoriteController::class, 'check']);
});

Route::group(['prefix' => 'save-promotions'], function () {
    Route::get('/', [SavePromotionController::class, 'index']);
    Route::post('/{uuid}', [SavePromotionController::class, 'toggle']);
    Route::get('/check/{uuid}', [SavePromotionController::class, 'check']);
});

Route::group(['prefix' => 'user-followers'], function () {
    Route::get('/', [UserFollowerController::class, 'index']);
    Route::post('/{userUuid}', [UserFollowerController::class, 'toggle']);
    Route::get('/check/{userUuid}', [UserFollowerController::class, 'check']);
});
    // Otras rutas protegidas...
});
