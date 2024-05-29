<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Provider;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use App\Http\Requests\SocialLoginRequest;
use App\Http\Resources\SocialLoginResource;
use App\Http\Resources\UserResource;
use Ramsey\Uuid\Uuid;
use App\Actions\Fortify\CreateNewUser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

use Illuminate\Routing\Controller as BaseController;

class SocialLoginController extends BaseController
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

     


   public function handleProviderCallback(SocialLoginRequest $request)
{
    try {
        $validatedData = $request->validated();
        
        // Validar el proveedor
        $provider = $this->validateProvider($validatedData['provider']);
        if ($provider instanceof \Illuminate\Http\JsonResponse) {
            return $provider;
        }

        // Obtener los datos del usuario del proveedor
        $providerUser = $this->getProviderUser($validatedData);

        // Validar el correo electrónico
        $email = $this->validateEmail($providerUser->getEmail());

        // Obtener el usuario por su correo electrónico
        $user = $this->getUserByEmail($email);

        if ($user) {
            $this->updateUser($user, $providerUser, $validatedData);
            $token = $this->createUserToken($user);
            $userResource = $this->cacheUserResource($user);
            return $this->successResponse($user, $token, $userResource);
        } else {
            return $this->newUserResponse($providerUser, $validatedData, $email);
        }
    } catch (\Throwable $e) {
        return $this->errorResponse($e);
    }
}

private function getProviderUser($validatedData)
{
    $cacheKey = 'provider_user_' . $validatedData['provider'] . '_' . md5($validatedData['access_provider_token']);
    return Cache::remember($cacheKey, 43200, function () use ($validatedData) {
        return Socialite::driver($validatedData['provider'])->userFromToken($validatedData['access_provider_token']);
    });
}

private function validateEmail($email)
{
    $validatedEmail = filter_var($email, FILTER_VALIDATE_EMAIL);
    if (!$validatedEmail) {
        throw new \Exception('Invalid email address from provider');
    }
    return $validatedEmail;
}

private function getUserByEmail($email)
{
    return Cache::remember('user_email_' . $email, 43200, function () use ($email) {
        return User::where('email', $email)->first();
    });
}

private function updateUser($user, $providerUser, $validatedData)
{
    if (!$user->email_verified_at) {
        $user->email_verified_at = now();
        $user->save();
        Cache::put('user_email_' . $user->email, $user, 43200);
    }

    $existingProvider = $user->providers()->firstWhere('provider', $validatedData['provider']);

    if ($existingProvider) {
        $existingProvider->update(['provider_avatar' => $providerUser->getAvatar()]);
    } else {
        $user->providers()->create([
            'uuid' => Uuid::uuid4()->toString(),
            'provider' => $validatedData['provider'],
            'provider_id' => $providerUser->getId(),
            'provider_avatar' => $providerUser->getAvatar(),
        ]);
    }

    Auth::login($user);
}

private function createUserToken($user)
{
    $token = $user->createToken('auth_token')->plainTextToken;
    return [
        'token' => explode('|', $token)[1],
        'created_at' => $user->tokens()->where('name', 'auth_token')->first()->created_at->format('Y-m-d H:i:s')
    ];
}

private function cacheUserResource($user)
{
    return Cache::remember("user_{$user->id}_resource", 43200, function () use ($user) {
        return new UserResource($user);
    });
}

private function successResponse($user, $token, $userResource)
{
    return response()->json([
        'message' => 'User logged successfully',
        'token' => $token['token'],
        'token_type' => 'Bearer',
        'token_created_at' => $token['created_at'],
        'user' => $userResource,
    ], 200);
}

private function newUserResponse($providerUser, $validatedData, $email)
{
    $userData = [
        'provider' => $validatedData['provider'],
        'provider_id' => $providerUser->getId(),
        'provider_avatar' => $providerUser->getAvatar(),
        'name' => $providerUser->getName(),
        'username' => $providerUser->getNickname(),
        'email' => $email,
    ];

    $userResource = new SocialLoginResource($userData);

    return response()->json([
        'message' => 'User fetched successfully from provider',
        'user' => $userResource,
    ], 200);
}

private function errorResponse(\Throwable $e)
{
    return response()->json(['message' => 'Error occurred: ' . $e->getMessage()], 500);
}


protected function validateProvider($provider)
{
    if (!in_array($provider, ['google','facebook', 'twitter'])) {
         return response()->json(["message" => 'You can only login via google, facebook, or twitter account'], 400);
    }
}

/**
     * Display the specified resource.
     */


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
