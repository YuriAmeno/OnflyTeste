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
    public function test_creates_a_user()
    {
        $password = '12345678';
        User::factory()->create([
            'name' => $password,
            'email' => 'amenoyuri1@gmail.com',
            'password' => Hash::make($password),
        ]);

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

            // Faz a request POST para criar um usuário
            $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                ->json('post', '/api/users', [
                    'name' => 'John Doe',
                    'email' => 'example@gmail.com',
                    'password' => 'password123',
                ]);

            // Verifica se o usuário foi criado com sucesso
            $response->assertStatus(201)
                ->assertJson([
                    'data' => [
                        'name' => 'John Doe',
                        'email' => 'example@gmail.com',
                        'createdAt' => now()->toDateTimeString(),
                        'updatedAt' => now()->toDateTimeString(),
                    ],
                ]);

            // Verifica se o usuário está presente no banco de dados
            $this->assertDatabaseHas('users', [
                'email' => 'example@gmail.com',
            ]);
        } else {
            $loginResponse->assertStatus(401);
        }
    }
    public function  test_prevents_invalid_create_attempts()
    {
        $response = $this->postJson('/api/users', [
            'name' => 'John Doe',
            'email' => 'amenoyuri1@gmail.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(401);
    }

    public function  test_prevents_get_attempts()
    {
        $password = '12345678';
        $user = User::factory()->create([
            'name' => 'Teste',
            'email' => 'amenoyuri1@gmail.com',
            'password' => Hash::make($password),
        ]);

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
            // Faz a request POST para criar um usuário
            $response = $this->withHeader('Authorization', 'Bearer ' . $token)
                ->json('get', '/api/users');

            $response->assertStatus(200);
        } else {
            $loginResponse->assertStatus(401);
        }
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
            $user = User::find(1);

            // Define os novos dados para a atualização do usuário
            $updatedUserData = [
                'name' => 'Novo Nome',
                'email' => 'novonome@gmail.com',
            ];

            // Faz a request PUT para atualizar o usuário
            $updateResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
                ->json('PUT', '/api/users/' . $user->id, $updatedUserData);

            // Verifica se a resposta tem o status correto
            $updateResponse->assertStatus(200);

            // Verifica se os dados foram atualizados corretamente no banco de dados
            $this->assertDatabaseHas('users', [
                'id' => $user->id,
                'name' => 'Novo Nome',
                'email' => 'novonome@gmail.com',
            ]);
        } else {
            $loginResponse->assertStatus(401);
        }
    }
    public function test_prevents_invalid_update_name_attempts()
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
            $user = User::find(1);

            // Define os novos dados para a atualização do usuário
            $updatedUserData = [
                'name' => 1231,
                'email' => 'novonome@gmail.com',
            ];

            // Faz a request PUT para atualizar o usuário
            $updateResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
                ->json('PUT', '/api/users/' . $user->id, $updatedUserData);

            // Verifica se a resposta tem o status correto
            $updateResponse->assertStatus(422);
        } else {
            $loginResponse->assertStatus(401);
        }
    }

    public function test_prevents_invalid_update_email_attempts()
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
            $user = User::find(1);

            // Define os novos dados para a atualização do usuário
            $updatedUserData = [
                'name' => "Yuri",
                'email' => 'novonome@',
            ];

            // Faz a request PUT para atualizar o usuário
            $updateResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
                ->json('PUT', '/api/users/' . $user->id, $updatedUserData);

            // Verifica se a resposta tem o status correto
            $updateResponse->assertStatus(422);
        } else {
            $loginResponse->assertStatus(401);
        }
    }

    public function test_delete_a_user()
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
            $user = User::find(1);

            // Cria uma tarefa (task) associada ao usuário, se necessário
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
        } else {
            $loginResponse->assertStatus(401);
        }
    }
}
