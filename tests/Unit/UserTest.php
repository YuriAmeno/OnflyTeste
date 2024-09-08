<?php

namespace Tests\Unit;

use App\Models\Task;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class UserTest extends TestCase
{
    use RefreshDatabase;
    public function createUserLogin()
    {
        $password = '12345678';
        $user = User::factory()->create([
            'name' => 'Yuri',
            'email' => 'amenoyuri' . time() . '@gmail.com',
            'password' => Hash::make($password),
        ]);
        return [
            'user' => $user,
            'password' => $password,
        ];
    }
    public function loginAndGetToken()
    {
        if (!User::first()) {
            $data = $this->createUserLogin();
            $user = $data['user'];
            $password = $data['password'];

            $loginResponse = $this->postJson('/api/login', [
                'email' => $user->email,
                'password' => $password,
            ]);

            $loginResponse->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'api_token',
                    ]
                ]);

            // Obtém o token da resposta
            $token = $loginResponse->json('data.api_token');

            // Retorna o token e o usuário logado
            return [
                'token' => $token,
                'user' => $user,
                'response' => $loginResponse
            ];
        }else{

            $user = User::first();
    
            $loginResponse = $this->postJson('/api/login', [
                'email' => $user->email,
                'password' => '12345678',
            ]);
    
            $loginResponse->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'api_token',
                    ]
                ]);
    
            // Obtém o token da resposta
            $token = $loginResponse->json('data.api_token');
    
            // Retorna o token e o usuário logado
            return [
                'token' => $token,
                'user' => $user,
                'response' => $loginResponse
            ];
        }

    }
    public function test_creates_a_user()
    {
        // Faz a request POST para criar um usuário
        $response = $this->withHeader('Authorization', 'Bearer ')
            ->json('post', '/api/first-login', [
                'name' => 'Yuri',
                'email' => 'amenoyuri1@gmail.com',
                'password' => '12345678',
            ]);

        // Verifica se o usuário foi criado com sucesso
        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => 'Yuri',
                    'email' => 'amenoyuri1@gmail.com',
                    'createdAt' => now()->toDateTimeString(),
                    'updatedAt' => now()->toDateTimeString(),
                ],
            ]);

        // Verifica se o usuário está presente no banco de dados
        $this->assertDatabaseHas('users', [
            'email' => 'amenoyuri1@gmail.com',
        ]);
    }
    public function  test_prevents_invalid_create_attempts()
    {
        $response = $this->postJson('/api/first-login', [
            'name' => 'aaa',
            'email' => 'amenoyuri1@',
            'password' => 'passrd123',
        ]);

        $response->assertStatus(422);
    }

    public function  test_prevents_get_attempts()
    {
        $auth = $this->loginAndGetToken();
        $token = $auth['token'];
        
        // Faz a request POST para criar um usuário
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->json('get', '/api/users');

        $response->assertStatus(200);
        
    }

    public function test_get_a_specific_user()
    {
        $password = '12345678';

        // Faz a request POST para login e pega o token
        $loginResponse = $this->postJson('/api/login', [
            'email' => 'amenoyuri1@gmail.com',
            'password' => $password,
        ]);
        if ($loginResponse->getStatusCode() === 200) {
            $loginResponse->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        'api_token',
                    ]
                ]);

            // Obtém o token da resposta
            $token = $loginResponse->json('data.api_token');

            // Faz uma request GET para pegar uma usuario específico
            $userResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
                ->json('GET', '/api/users/1');

            $userResponse->assertStatus(200)
                ->assertJsonStructure([
                    'name',
                    'email',
                    'createdAt',
                    'updatedAt',
                ]);
        } else {
            $loginResponse->assertStatus(401);
        }
    }

    public function test_update_a_user()
    {
        $auth = $this->loginAndGetToken();
        $token = $auth['token'];
        $user = $auth['user'];

        // Define os novos dados para a atualização do usuário
        $updatedUserData = [
            'name' => 'Yuri',
            'email' => 'amenoyuri1@gmail.com',
        ];

        // Faz a request PUT para atualizar o usuário
        $updateResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->json('PUT', '/api/users/' . $user->id, $updatedUserData);

        // Verifica se a resposta tem o status correto
        $updateResponse->assertStatus(200);

        // Verifica se os dados foram atualizados corretamente no banco de dados
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Yuri',
            'email' => 'amenoyuri1@gmail.com',
        ]);
    }
    public function test_prevents_invalid_update_name_attempts()
    {
        $auth = $this->loginAndGetToken();
        $token = $auth['token'];
        $user = $auth['user'];

        $updatedUserData = [
            'name' => 1231,
            'email' => 'teste@gmail.com',
        ];

        // Faz a request PUT para atualizar o usuário
        $updateResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->json('PUT', '/api/users/' . $user->id, $updatedUserData);

        // Verifica se a resposta tem o status correto
        $updateResponse->assertStatus(400);
       
    }

    public function test_prevents_invalid_update_email_attempts()
    {
        $auth = $this->loginAndGetToken();
        $token = $auth['token'];
        $user = $auth['user'];
        // Define os novos dados para a atualização do usuário
        $updatedUserData = [
            'name' => "Yuri",
            'email' => 'novonome@',
        ];

        // Faz a request PUT para atualizar o usuário
        $updateResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->json('PUT', '/api/users/' . $user->id, $updatedUserData);

        // Verifica se a resposta tem o status correto
        $updateResponse->assertStatus(400);
    }

    public function test_delete_a_user()
    {
        $auth = $this->loginAndGetToken();
        $token = $auth['token'];
        $user = $auth['user'];
        Task::factory()->create([
            'user_id' => $user->id,
            'description' => 'Teste',
            'value' => 523,
            'data' => now()->toDateTimeString()
        ]);


        // Faz a request delete para excluir o usuário
        $deleteResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->json('DELETE', '/api/users/' . $user->id);

        // Verifica se a resposta tem o status correto
        $deleteResponse->assertStatus(200);

        // Verifica se a despesa vinculada aquele usuario foi removida do banco de dados
        $this->assertDatabaseMissing('tasks', [
            'user_id' => $user->id,
        ]);

        // Verifica se o usuário foi removido do banco de dados
        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }
}
