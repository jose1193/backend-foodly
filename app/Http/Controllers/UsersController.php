<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use Hash;
use Illuminate\Support\Arr;
use App\Actions\Jetstream\DeleteUser;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\UserResource;
use Ramsey\Uuid\Uuid;
use Illuminate\Routing\Controller as BaseController;


class UsersController extends BaseController
{

    // PERMISSIONS USERS
    public function __construct()
{
   $this->middleware('check.permission:Super Admin')->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);

}

    
    // SHOW LIST OF USERS
    public function index(Request $request)
{
    try {
        // Obtener todos los usuarios, incluidos los eliminados
        $users = User::withTrashed()->orderBy('id', 'DESC')->get();

        // Crear una colección de recursos de usuarios
        $userResources = UserResource::collection($users);

        // Devolver una respuesta JSON con los recursos de usuarios
        return response()->json(['users' => $userResources], 200);
    } catch (\Exception $e) {
        // Manejar cualquier excepción y devolver una respuesta de error
        return response()->json(['message' => 'Error occurred while fetching users'], 500);
    }
}

    // SYNC ROLES
    public function create()
    {
        $roles = Role::orderBy('id', 'DESC')->get();
        return response()->json(['roles' => $roles], 200);
    }

    // STORE USER
   
public function store(Request $request)
{
    try {
        // Validar datos de entrada
        $request->validate([
            'email' => ['required', 'email', 'unique:users,email'],
            'username' => ['required', 'unique:users,username'],
            // Agrega otras reglas de validación según sea necesario
        ]);

        // Hash de la contraseña
        $input = $request->all();
        $input['password'] = Hash::make($input['password']);

        // Generar UUID
        $input['uuid'] = Uuid::uuid4()->toString();

        // Crear usuario
        $user = User::create($input);

        // Sincronizar roles del usuario
        $this->syncRoles($user, $request->input('role_id'));

        // Obtener y agregar información de roles al usuario
        $userRole = $user->roles->pluck('name')->first() ?? null;
        $roleId = $user->roles->pluck('id')->first() ?? null;
        $user->user_role = $userRole;
        $user->role_id = $roleId;

        // Crear el recurso de usuario
        $userResource = new UserResource($user);

        return $userResource;
    } catch (\Illuminate\Validation\ValidationException $e) {
        // Manejar errores de validación
        return response()->json(['errors' => $e->errors()], 422);
    } catch (\Exception $e) {
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
        $this->syncRoles($user, $request->input('roles'));

        // Devolver el recurso UserResource con la variable $user
        return new UserResource($user);
    } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
        return response()->json(['message' => 'User not found'], 404);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json(['errors' => $e->errors()], 422);
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
        if (!empty($input['password']) && $input['password'] != $user->password) {
            $input['password'] = bcrypt($input['password']);
        } else {
            $input['password'] = $user->password;
        }
    }

    // SHOW PROFILE USER
   public function show($uuid)
{
    try {
        // Buscar el usuario por su UUID
        $user = User::withTrashed()->where('uuid', $uuid)->first();

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
    public function destroy($uuid, DeleteUser $deleteUser)
{
    $user = User::where('uuid', $uuid)->first();

    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }

    $deleteUser->delete($user);

    return response()->json(['message' => 'User deleted successfully'], 200);
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

        // Restaurar el usuario eliminado
        $user->restore();

        // Devolver una respuesta JSON con el mensaje y el recurso del usuario restaurado
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
