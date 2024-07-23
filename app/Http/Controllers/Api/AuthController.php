<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Auth;
use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *      version="1.0.0",
 *      title="Laravel API",
 *     description="Laravel API Documentation",
 * )
 */
class AuthController extends Controller
{
    /**
     * @OA\Post(
     *      path="/api/v1/auth/login",
     *      operationId="login",
     *      tags={"Authentication"},
     *      summary="Login",
     *      description="Login by email, password",
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"email","password"},
     *              @OA\Property(property="email", type="string", format="email", example="test@test.com"),
     *              @OA\Property(property="password", type="string", format="password", example="123")
     *         ),
     *     ),
     *     @OA\Response( response=200, description="Success",
     *      @OA\JsonContent(
     *          @OA\Property(property="access_token", type="string", format="bearer", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJzdWIiOiI1IiwiaXNzIjoiYXBpLmNvbSIsImlhdCI6MTYxNzQwNzYwMCwiZXhwIjoxNjE3NDA3NjAwfQ"),
     *          @OA\Property(property="token_type", type="string", example="bearer"),
     *          @OA\Property(property="expires_in", type="integer", example="3600")
     *    )),
     *     @OA\Response( response=401, description="Unauthorized",
     *     @OA\JsonContent(
     *     @OA\Property(property="error", type="string", example="Unauthorized")
     *    )),
     * )
     */
    public function login()
    {
        $credentials = request(['email', 'password']);

        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        # Se todas as credenciais estiverem corretas, geraremos um novo token de acesso e o enviaremos de volta na resposta
        return $this->respondWithToken($token);
    }

    /**
     * @OA\Post(
     *      path="/api/v1/auth/logout",
     *      operationId="logout",
     *      tags={"Authentication"},
     *      summary="Logout",
     *      description="Logout",
     *      security={{"jwt": {}}},
     *      @OA\Response( response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="message", type="string", example="Successfully logged out")
     *    )),
     * )
     */

    public function logout()
    {
        auth()->logout(true); # Invalida o token atual do usuário

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
