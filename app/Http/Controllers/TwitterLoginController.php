<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\TwitterUser;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\SocialLoginRequest;

class TwitterLoginController extends Controller
{
    public function handleProvider(SocialLoginRequest $request)
    {
        try {
            $validated = $request->validated();

            // Cambia el nombre de la variable para que sea más claro.
            $authorizationCode = $validated['access_provider_token'];
            $codeVerifier = $validated['code_verifier'];

            $tokenData = $this->getTwitterAccessToken($authorizationCode, $codeVerifier);
            $userData = $this->getTwitterUserData($tokenData['access_token']);

            return new TwitterUser($userData); // Asegúrate de que TwitterUser maneja correctamente la respuesta.

        } catch (\Exception $e) {
            Log::error("Twitter auth error: " . $e->getMessage());
            return response()->json([
                'error' => 'Authentication failed',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    private function getTwitterAccessToken($code, $codeVerifier)
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Authorization' => 'Basic ' . base64_encode(config('services.twitter.client_id') . ':' . config('services.twitter.client_secret'))
        ])->post('https://api.twitter.com/2/oauth2/token', [
            'code' => $code, // Usamos el código de autorización aquí
            'grant_type' => 'authorization_code',
            'redirect_uri' => config('services.twitter.redirect'),
            'code_verifier' => $codeVerifier
        ]);

        if ($response->failed()) {
            throw new \Exception("Token error: " . $response->body());
        }

        return $response->json();
    }

    private function getTwitterUserData($accessToken)
    {
        $response = Http::withToken($accessToken)
            ->get('https://api.twitter.com/2/users/me', [
                'user.fields' => 'id,name,profile_image_url,username'
            ]);

        if ($response->failed()) {
            throw new \Exception("User data error: " . $response->body());
        }

        return $response->json('data');
    }
}