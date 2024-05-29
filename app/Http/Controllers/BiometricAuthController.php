<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Http\FormRequest;
use App\Http\Resources\UserResource;
use App\Http\Controllers\Controller;


class BiometricAuthController extends Controller
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
    public function store(Request $request)
{
    try {
        // Verifica si el usuario está autenticado
        $user = auth()->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated user'], 401);
        }

        // Elimina el token de acceso actual del usuario
        $request->user()->currentAccessToken()->delete();

        // Crea un nuevo token de acceso para el usuario
        $token = $user->createToken('auth_token')->plainTextToken;

        // Establece una cookie con el nuevo token (opcional)
        $cookie = cookie('token', $token, 60 * 24 * 365); // 1 año de duración

        // Recurso del usuario (opcional)
        $userResource = new UserResource($user);

        // Formatea la fecha de creación del token (opcional)
        $formattedTokenCreatedAt = now()->format('Y-m-d H:i:s');

        // Devuelve la respuesta con los datos relevantes
        return response()->json([
            'user' => $userResource,
            'token' => explode('|', $token)[1],
            'token_type' => 'Bearer',
            'token_created_at' => $formattedTokenCreatedAt,
            'message' => 'User logged successfully',
        ], 201)->withCookie($cookie); // Asigna la cookie a la respuesta

    } catch (\Exception $e) {
        // En caso de error, devuelve una respuesta de error
        return response()->json(['error' => 'Internal Server Error'], 500);
    }
}


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
