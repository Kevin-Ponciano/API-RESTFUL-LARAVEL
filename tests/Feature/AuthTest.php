<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private string $url = '/api/v1/auth';

    /*
     * Teste de autenticação de usuário
    */
    public function test_user_can_login()
    {
        $email = 'user_test@test.com';
        $password = '12345';

        User::factory()->create([
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $response = $this->postJson("{$this->url}/login", [
            'email' => $email,
            'password' => $password
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['access_token', 'token_type', 'expires_in'])
            ->assertJson($response->json());
    }

    /*
     * Teste de logout de usuário
    */
    public function test_user_can_logout()
    {
        $email = 'user_test@test.com';
        $password = '12345';

        User::factory()->create([
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $token = $this->postJson("{$this->url}/login", [
            'email' => $email,
            'password' => $password
        ])->json('access_token');

        $response = $this->postJson("{$this->url}/logout", headers: [
            'Authorization' => 'Bearer ' . $token
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Successfully logged out']);
    }


    /*
     * Teste de atualização de token
    */
    public function test_user_not_found()
    {
        $email = 'user_test@test.com';
        $password = '12345';

        User::factory()->create([
            'email' => $email,
            'password' => Hash::make($password),
        ]);

        $response = $this->postJson("{$this->url}/login", [
            'email' => 'teste@teste.com',
            'password' => $password
        ]);

        $response->assertStatus(401)
            ->assertJson(['error' => 'Unauthorized']);

    }
}
