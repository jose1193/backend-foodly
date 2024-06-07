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
use App\Http\Controllers\PromotionCoverImageController;
use App\Http\Controllers\PromotionBranchController;
use App\Http\Controllers\PromotionBranchImageController;
use App\Http\Controllers\SocialLoginController;
use App\Http\Controllers\CreateUserController;
use App\Http\Controllers\PasswordResetUserController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TwitterController;
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


Route::controller(PasswordResetUserController::class)->group(function () {
    Route::post('/forgot-password', 'store'); 
    Route::post('/enter-pin', 'verifyResetPassword');
    Route::post('/reset-password', 'updatePassword');  
   
});

Route::get('/services', [ServiceController::class, 'index']);


//Route::controller(RegisterController::class)->group(function(){
    //Route::post('register', 'register');
    //Route::post('login', 'login');
//});

Route::get('auth/twitter', [TwitterController::class, 'redirectToTwitter']);
Route::post('twitter/callback', [TwitterController::class, 'handleTwitterCallback']);
Route::post('/twitter/user-details', [TwitterController::class, 'getUserDetails']);



Route::middleware(['auth.routes','handle.notfound','token.auth'])->group(function() {
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
    

    // Rutas relacionadas con roles
    Route::get('roles-list', [RoleController::class, 'index']); // Obtener una lista de roles
    Route::post('roles', [RoleController::class, 'store']); // Crear un nuevo rol
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

    // Routes related to Business Cover Images
    Route::get('/promotions', [PromotionController::class, 'index']);
    Route::post('/promotions-store', [PromotionController::class, 'store']);
    Route::put('/promotions-update/{uuid}', [PromotionController::class, 'update']);
    Route::get('/promotions/{uuid}', [PromotionController::class, 'show']);
    Route::delete('/promotions-delete/{uuid}', [PromotionController::class, 'destroy']);
    Route::put('/promotions-restore/{uuid}', [PromotionController::class, 'restore']);


    // Routes related to Promotions Business Images
    Route::get('/promotions-images', [PromotionCoverImageController::class, 'index']);
    Route::post('/promotions-images-store', [PromotionCoverImageController::class, 'store']);
    Route::get('/promotions-images/{uuid}', [PromotionCoverImageController::class, 'show']);
    Route::delete('/promotions-images-delete/{uuid}', [PromotionCoverImageController::class, 'destroy']);
    Route::post('/promotions-images-update/{uuid}', [PromotionCoverImageController::class, 'updateImage']);
    
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


    // Otras rutas protegidas...
});
    
