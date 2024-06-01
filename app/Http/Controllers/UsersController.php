<?php

namespace App\Http\Controllers;
use App\Http\Controllers\BaseController as BaseController;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use Hash;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use App\Http\Resources\UserResource;
use Ramsey\Uuid\Uuid;



class UsersController extends BaseController
{

    protected $cacheKey;
    
    protected $cacheTime = 720;
    protected $userId;


    public function __construct()
{
   $this->middleware('check.permission:Super Admin')->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

    $this->middleware(function ($request, $next) {
            $this->userId = Auth::id();
            $this->cacheKey = 'users_' .  $this->userId . '_total_list';
            return $next($request);
        });

}

    
    // SHOW LIST OF USERS
    public function index(Request $request)
{
    try {
        if (!Auth::check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

       
        $users = $this->getCachedData($this->cacheKey, $this->cacheTime, function () {
            return User::withTrashed()->orderBy('id', 'DESC')->get();
        });
        
        $this->updateUsersCache();

        
        $userResources = UserResource::collection($users);

        
        return response()->json(['users' => $userResources], 200);
    } catch (\Illuminate\Database\QueryException $e) {
        
        return response()->json(['message' => 'Database error occurred while fetching users'], 500);
    } catch (\Exception $e) {
        
        return response()->json(['message' => 'Error occurred while fetching users'], 500);
    }
}

private function updateUsersCache()
{
    $this->refreshCache($this->cacheKey, $this->cacheTime, function () {
         return User::withTrashed()->orderBy('id', 'DESC')->get();
    });
}

    // SYNC ROLES
    public function create()
{
    $cacheKey = 'roles_list';
    $roles = $this->getCachedData($cacheKey, 360, function () {
        return Role::orderBy('id', 'DESC')->get();
    });
    return response()->json(['roles' => $roles], 200);
}

    // STORE USER
   
public function store(Request $request)
{
    // Validar datos de entrada
    $request->validate([
        'email' => ['required', 'email', 'unique:users,email'],
        'username' => ['required', 'unique:users,username'],
        'password' => ['required', 'min:8'], // Agregar otras reglas de validación según sea necesario
    ]);

    DB::beginTransaction();

    try {
        // Preparar los datos de entrada
        $input = $request->all();
        $input['password'] = Hash::make($input['password']);
        $input['uuid'] = Uuid::uuid4()->toString();

        // Crear usuario
        $user = User::create($input);

        // Sincronizar roles del usuario
        $this->syncRoles($user, $request->input('role_id'));

        // Obtener el primer rol del usuario y asignarlo a los atributos adicionales
        $role = $user->roles->first();
        $user->user_role = $role->name ?? null;
        $user->role_id = $role->id ?? null;

        // Crear el recurso de usuario
        $userResource = new UserResource($user, 200);

        // Actualizar caché de usuarios
        $this->updateUsersCache();

        DB::commit();

        return $userResource;

    } catch (\Illuminate\Validation\ValidationException $e) {
        DB::rollBack();
        // Manejar errores de validación
        return response()->json(['errors' => $e->errors()], 422);
    } catch (\Exception $e) {
        DB::rollBack();
        // Manejar otros errores
        return response()->json(['message' => 'Error occurred while creating user'], 500);
    }
}




    // UPDATE USER
   public function update(Request $request, $uuid)
{
    try {
        $this->validateUser($request, $uuid);

        $user = User::where('uuid', $uuid)->firstOrFail();
        $input = $request->all();

        $this->updatePassword($user, $input);

        $user->update($input);
        $this->syncRoles($user, $request->input('role_id'));

        // Actualizar caché de usuarios
        $this->updateUsersCache();

        // Devolver el recurso UserResource con la variable $user
        return new UserResource($user);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json(['message' => 'User not found'], 404);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json(['errors' => $e->errors()], 422);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Error occurred while updating user'], 500);
    }
}



    // FIELDS VALIDATION RULES
    private function validateUser(Request $request, $id = null)
    {
    $rules = [
        'name' => 'required',
        'email' => 'required|email|unique:users,email,' . $id,
        'phone' => 'required|min:6|max:20',
        'address' => 'required|min:3|max:255',
        'zip_code' => 'required|min:3|max:255',
        'city' => 'required|min:3|max:255',
        'country' => 'required|min:3|max:255',
        'gender' => 'required|min:3|max:255',
        'role_id' => 'required',
    ];

    // Añadir reglas de validación para el password solo en ciertos casos
    if ($id === null || $request->filled('password')) {
        $rules['password'] = [
            'sometimes',
            'nullable',
            'regex:/^\S+$/',
            'min:4',
            'max:20',
        ];
    }

         $this->validate($request, $rules);
    }


    // SYNC ROLES
    private function syncRoles(User $user, $roles)
    {
        $user->roles()->sync($roles);
    }


    // UPDATE PASSWORD USER
    private function updatePassword(User $user, array &$input)
{
    
    if (!empty($input['password'])) {
        // Encriptar la nueva contraseña
        $input['password'] = bcrypt($input['password']);
    } else {
        // Mantener la contraseña existente
        $input['password'] = $user->password;
    }
}


    // SHOW PROFILE USER
   public function show($uuid)
{
    try {
        $cacheKey = 'user_' . $uuid;
        
        // Buscar el usuario en caché o en la base de datos
        $user = $this->getCachedData($cacheKey, $this->cacheTime, function () use ($uuid) {
            return User::withTrashed()->where('uuid', $uuid)->first();
        });

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Devolver una respuesta JSON con el recurso UserResource del usuario
        return response()->json(['user' => new UserResource($user)], 200);
    } catch (\Exception $e) {
        // Manejar cualquier excepción y devolver una respuesta de error
        return response()->json(['message' => 'Error occurred while fetching user'], 500);
    }
}





    public function edit($id)
    {
        $user = User::find($id);
        $roles = Role::pluck('name', 'name')->all();
        $userRole = $user->roles->pluck('name', 'name')->all();

        return response()->json(['user' => $user, 'roles' => $roles, 'userRole' => $userRole], $user ? 200 : 404);
    }





    // USER DELETE
    public function destroy($uuid)
{
    try {
        // Buscar el usuario por su UUID
        $user = User::where('uuid', $uuid)->firstOrFail();

        // Eliminar el usuario (soft delete)
        $user->delete();

        // Invalidar el caché del usuario
        $this->invalidateCache('user_' . $uuid);

        return response()->json(['message' => 'User deleted successfully'], 200);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        // Manejar el caso donde el usuario no fue encontrado
        return response()->json(['message' => 'User not found'], 404);
    } catch (\Exception $e) {
        // Manejar cualquier otra excepción y devolver una respuesta de error
        return response()->json(['message' => 'Error occurred while deleting user'], 500);
    }
}



public function restore($uuid)
{
    try {
        // Validar si el UUID proporcionado es válido
        if (!Uuid::isValid($uuid)) {
            return response()->json(['message' => 'Invalid UUID'], 400);
        }

        // Buscar el usuario eliminado con el UUID proporcionado
        $user = User::where('uuid', $uuid)->onlyTrashed()->first();

        if (!$user) {
            return response()->json(['message' => 'User not found in trash'], 404);
        }

        // Verificar si el usuario ya ha sido restaurado
        if (!$user->trashed()) {
            return response()->json(['message' => 'User already restored'], 400);
        }

        
        $user->restore();

        // Invalidar el caché del usuario
        $this->invalidateCache('user_' . $uuid);
       
        $this->updateUsersCache();

       
        return response()->json([
            'message' => 'User restored successfully',
            'user' => new UserResource($user)
        ], 200);
    } catch (\Exception $e) {
        // Manejar cualquier excepción y devolver una respuesta de error
        return response()->json(['message' => 'Error occurred while restoring User'], 500);
    }
}



}
