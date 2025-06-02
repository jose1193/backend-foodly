<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Hash;

use Laravel\Fortify\Contracts\CreatesNewUsers;

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\Provider;
use App\Helpers\ImageHelper;
use App\Http\Requests\CreateUserRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class CreateUserController extends Controller 
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
  public function store(CreateUserRequest $request) {
    DB::beginTransaction();
    
    try {
        // Validar y extraer datos.
        $data = $request->validated();
        $user = $this->createUser($data);
        
        // Asignar roles y otros datos sin dependencia de la subida de archivos.
        $this->assignUserRole($data, $user);
        $this->handleUserProviderData($request, $data, $user);
        
        // Handle addresses (new system)
        $this->handleUserAddresses($data, $user);
        
        // Crear token de usuario.
        $tokenData = $this->createUserToken($user);
        
        // Manejar foto de perfil
        $this->handleUserProfilePhoto($request, $user);
        
        // Confirmar todas las operaciones.
        DB::commit();
        
        // Crear una cookie con el token
        //$cookie = $this->createCookieForToken($tokenData['token']);
        
        // Devolver respuesta exitosa con datos del usuario y token.
        return response()->json([
            'message' => 'User created successfully',
            'token' => $tokenData['token'],
            'token_type' => 'Bearer',
            'token_created_at' => $tokenData['created_at'],
            'user' => new UserResource($user)
        ], 200);
        
    } catch (\Exception $e) {
        // Revertir todos los cambios en caso de error.
        DB::rollback();
        return response()->json(['error' => $e->getMessage()], 422);
    }
}

private function handleUserProfilePhoto(CreateUserRequest $request, User $user)
{
    if ($request->hasFile('photo')) {
        $photoPath = ImageHelper::storeAndResize($request->file('photo'), 'public/profile-photos');
        // Asegurarse de que la foto solo se asigne si se ha guardado correctamente.
        if ($photoPath) {
            $user->update(['profile_photo_path' => $photoPath]);
        }
    }
}

private function createUser(array $data): User
{
    $data['uuid'] = Uuid::uuid4()->toString();
    $data['password'] = Hash::make($data['password']);
    return User::create($data);
}

private function assignUserRole(array $data, User $user)
{
    $role = Role::find($data['role_id']);
    if (!$role) {
        throw new \Exception('Invalid role ID');
    }
    $user->assignRole($role);
}

private function handleUserProviderData(CreateUserRequest $request, array $data, User $user)
{
    if (isset($data['provider_id'], $data['provider'], $data['provider_avatar'])) {
        Provider::create([
            'uuid' => Uuid::uuid4()->toString(),
            'provider_id' => $data['provider_id'],
            'provider' => $data['provider'],
            'provider_avatar' => $data['provider_avatar'],
            'user_id' => $user->id,
        ]);
        if (!$user->email_verified_at) {
            $user->email_verified_at = now();
            $user->save();
        }
    } elseif ($request->has('provider_id') || $request->has('provider') || $request->has('provider_avatar')) {
        throw new \Exception('Incomplete provider data');
    }
}

private function createUserToken(User $user): array {
    $userToken = $user->createToken('API Token User Register')->plainTextToken;
    $token = PersonalAccessToken::findToken(explode('|', $userToken)[1]);
    $formattedTokenCreatedAt = $token ? $token->created_at->format('Y-m-d H:i:s') : null;
    
    return ['token' => explode('|', $userToken)[1], 'created_at' => $formattedTokenCreatedAt];
}

private function createCookieForToken($token) {
     return cookie('token', $token, 60 * 24 * 365); 
}

private function handleUserAddresses(array $data, User $user)
{
    // Handle new addresses array
    if (isset($data['addresses']) && is_array($data['addresses'])) {
        $usedLabels = [];
        
        foreach ($data['addresses'] as $index => $addressData) {
            // Check for duplicate labels in the same request
            if (in_array($addressData['address_label_id'], $usedLabels)) {
                throw ValidationException::withMessages(['addresses' => ['Each address must have a unique label.']]);
            }
            $usedLabels[] = $addressData['address_label_id'];
            
            // Set first address as principal if not specified
            if ($index === 0 && !isset($addressData['principal'])) {
                $addressData['principal'] = true;
            }
            
            $addressData['user_id'] = $user->id;
            $user->addresses()->create($addressData);
        }
    } 
    // Handle legacy address fields for backward compatibility
    elseif (isset($data['address']) || isset($data['city']) || isset($data['country']) || isset($data['zip_code'])) {
        // Get default "Home" label for legacy addresses
        $homeLabel = \App\Models\AddressLabel::where('name', 'Home')->first();
        if (!$homeLabel) {
            throw new \Exception('Default "Home" address label not found. Please run the address labels seeder.');
        }
        
        $legacyAddress = [
            'user_id' => $user->id,
            'address' => $data['address'] ?? '',
            'city' => $data['city'] ?? '',
            'country' => $data['country'] ?? '',
            'zip_code' => $data['zip_code'] ?? '',
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'address_label_id' => $homeLabel->id,
            'principal' => true,
        ];
        
        $user->addresses()->create($legacyAddress);
    }
}

private function updateUserAddresses(array $data, User $user)
{
    // Handle new addresses array
    if (isset($data['addresses']) && is_array($data['addresses'])) {
        $usedLabels = [];
        
        // Validate for duplicate labels in the request
        foreach ($data['addresses'] as $addressData) {
            if (in_array($addressData['address_label_id'], $usedLabels)) {
                throw ValidationException::withMessages(['addresses' => ['Each address must have a unique label.']]);
            }
            $usedLabels[] = $addressData['address_label_id'];
        }
        
        // Delete existing addresses and create new ones
        $user->addresses()->delete();
        foreach ($data['addresses'] as $index => $addressData) {
            // Set first address as principal if not specified
            if ($index === 0 && !isset($addressData['principal'])) {
                $addressData['principal'] = true;
            }
            
            $addressData['user_id'] = $user->id;
            $user->addresses()->create($addressData);
        }
    } 
    // Handle legacy address fields for backward compatibility
    elseif (isset($data['address']) || isset($data['city']) || isset($data['country']) || isset($data['zip_code'])) {
        // Get default "Home" label for legacy addresses
        $homeLabel = \App\Models\AddressLabel::where('name', 'Home')->first();
        if (!$homeLabel) {
            throw new \Exception('Default "Home" address label not found. Please run the address labels seeder.');
        }
        
        // Update or create principal address with legacy data
        $principalAddress = $user->addresses()->where('principal', true)->first();
        
        $legacyAddressData = [
            'address' => $data['address'] ?? '',
            'city' => $data['city'] ?? '',
            'country' => $data['country'] ?? '',
            'zip_code' => $data['zip_code'] ?? '',
            'latitude' => $data['latitude'] ?? null,
            'longitude' => $data['longitude'] ?? null,
            'address_label_id' => $homeLabel->id,
            'principal' => true,
        ];
        
        if ($principalAddress) {
            $principalAddress->update($legacyAddressData);
        } else {
            $legacyAddressData['user_id'] = $user->id;
            $user->addresses()->create($legacyAddressData);
        }
    }
}

//protected function createUser(array $input): User
//{
    //return User::create([
        //'name' => $input['name'],
       // 'last_name' => $input['last_name'],
        //'username' => $input['username'],
        //'date_of_birth' => $input['date_of_birth'],
        //'uuid' => Uuid::uuid4()->toString(),
        //'email' => $input['email'],
        //'password' => Hash::make($input['password']),
        //'phone' => $input['phone'],
        //'address' => $input['address'],
        //'zip_code' => $input['zip_code'],
        //'city' => $input['city'],
        //'country' => $input['country'],
        //'gender' => $input['gender'],
    //]);
//}
    /**
     * Display the specified resource.
     */
   
    /**
     * Update the specified resource in storage.
     */


public function update(CreateUserRequest $request)
{
    DB::beginTransaction();

    try {
        $user = Auth::user();
        $data = $request->validated();

        // Log latitude y longitude
        \Log::info('Update Coordinates:', [
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'validated_latitude' => $data['latitude'] ?? null,
            'validated_longitude' => $data['longitude'] ?? null
        ]);

        if ($user->id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $this->validateEmail($data, $user);
        $this->validateUsername($data, $user);

        // Update role if role_id is provided
        if (isset($data['role_id'])) {
            $this->validateRoleId($data);
            $role = Role::find($data['role_id']);
            $user->syncRoles($role);
        }

        // Handle addresses update
        $this->updateUserAddresses($data, $user);

        // Remove address-related fields and role_id from data before updating user
        unset($data['password'], $data['role_id'], $data['addresses'], 
              $data['address'], $data['city'], $data['country'], 
              $data['zip_code'], $data['latitude'], $data['longitude']);

        $user->update($data);

        DB::commit();

        $this->updateUserCache($user);

        return response()->json([
            'message' => 'Profile updated successfully', 
            'user' => new UserResource($user)
        ], 200);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['error' => $e->getMessage()], 422);
    }
}

private function validateEmail($data, $user)
{
    if (isset($data['email']) && $user->email !== $data['email']) {
        $existingEmailUser = User::where('email', $data['email'])->first();
        if ($existingEmailUser) {
            response()->json(['message' => 'Email already taken'], 409)->throwResponse();
        }
    }
}

private function validateUsername($data, $user)
{
    if (isset($data['username']) && $user->username !== $data['username']) {
        $existingUsernameUser = User::where('username', $data['username'])->first();
        if ($existingUsernameUser) {
            response()->json(['message' => 'Username already taken'], 409)->throwResponse();
        }
    }
}

private function validateRoleId($data)
{
    if (isset($data['role_id'])) {
        $roleExists = Role::where('id', $data['role_id'])->exists();
        if (!$roleExists) {
            response()->json(['message' => 'Role ID does not exist'], 409)->throwResponse();
        }
    }
}

private function updateUserCache($user)
{
    // Invalidar la caché del usuario
    Cache::forget('user_' . $user->id);

    // Actualizar la caché del usuario
    $userResource = new UserResource($user);
    Cache::put('user_' . $user->id, $userResource, now()->addMinutes(60));
}




    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}