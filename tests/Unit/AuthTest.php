<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class AuthTest extends TestCase
{
    use RefreshDatabase;
    /** @test */
    public function it_allows_user_to_login()
    {
        // Cria um user fake 
        $password = '12345678';
        $user = User::factory()->create([
            'name' => 'Yuri',
            'email' => 'amenoyuri1@gmail.com',
            'password' => Hash::make($password),
        ]);

        // Faz a request POST para o login
        $response = $this->postJson('/api/login', [
            'email' => 'amenoyuri1@gmail.com',
            'password' => $password,
        ]);

        // Verifica se o login foi bem-sucedido
        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'api_token',
                ]
            ]);

        // Armazena o token de autenticação
        $token = $response->json('data.api_token');

        // Faz uma request GET autenticada para verificar a criação de um usuário
        $response = $this->withHeaders([
            'Authorization' => "Bearer $token",
        ])->getJson('/api/users');

        // Verifica se a requisição GET autenticada foi bem-sucedida
        $response->assertStatus(200);
    }


    /** @test */
    public function test_prevents_invalid_login_attempts()
    {
        // Faz a tentativa de login com dados inválidos
        $response = $this->postJson('/api/login', [
            'email' => 'amenoyuri1@',
            'password' => '123456',
        ]);

        // Verifica se a resposta de erro é correta
        $response->assertStatus(422);
        // Verifica se nenhum usuário foi autenticado
        $this->assertGuest();
    }

    public function test_prevents_unauthorized_login_attempts()
    {
        // Faz a tentativa de login com dados inválidos
        $response = $this->postJson('/api/login', [
            'email' => 'amenoyuri1@gmail.com',
            'password' => '123456',
        ]);

        // Verifica se a resposta de erro é correta
        $response->assertStatus(401);

        // Verifica se nenhum usuário foi autenticado
        $this->assertGuest();
    }
}
