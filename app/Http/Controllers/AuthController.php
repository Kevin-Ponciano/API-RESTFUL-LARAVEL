<?php

namespace App\Http\Controllers;

class AuthController extends Controller
{
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        # Se todas as credenciais estiverem corretas, geraremos um novo token de acesso e o enviaremos de volta na resposta
        return $this->respondWithToken($token);
    }

    public function logout()
    {
        auth()->logout(); # Esta é apenas uma função de logout que destruirá o token de acesso do usuário atual

        return response()->json(['message' => 'Successfully logged out']);
    }

    protected function respondWithToken(string $token)
    {
        # Esta função é usada para fazer resposta JSON com novos
        # token de acesso do usuário atual
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
}
