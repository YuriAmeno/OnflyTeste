<?php

namespace Tests\Unit;

use App\Jobs\EnviarEmailJob;
use App\Models\Task;
use App\Models\User;
use App\Notifications\EnviarEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Faker\Factory as Faker;

class TaskTest extends TestCase
{

    public function createUserLogin()
    {
        $password = '12345678';
        $user = User::factory()->create([
            'name' => 'Charles',
            'email' => 'ycameno' . time() . '@gmail.com',
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
        }

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

    public function test_create_a_task()
    {
        Queue::fake();
        Notification::fake();

        $auth = $this->loginAndGetToken();
        $token = $auth['token'];
        $user = $auth['user'];

        // Faz a request POST para criar um usuário
        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->json('post', '/api/tasks', [
                'id' => 1,
                'description' => 'Venda NIKE',
                'value' => 199,
                'user_id' => $user->id,
                'data' => '2024-09-08',
            ]);


        // Verifica se o usuário foi criado com sucesso
        $response->assertStatus(201)
            ->assertJson([
                'id' => 1, // Garantir que o ID está correto
                'description' => 'Venda NIKE',
                'data' => now()->toDateString(),
                'value' => '199.00',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'createdAt' => now()->toDateTimeString(),
                'updatedAt' => now()->toDateTimeString(),
            ]);

        // Verifica se a task foi criada no banco de dados
        $this->assertDatabaseHas('tasks', [
            'description' => 'Venda NIKE',
            'value' => 199,
            'user_id' => $user->id,
        ]);

        $taskId = $response->json('id');
        $task = Task::find($taskId);

        $user =  $task->user;

        EnviarEmailJob::dispatch($user, $task);
        Queue::assertPushed(EnviarEmailJob::class, function ($job) use ($user, $task) {
            return $job->user->id === $user->id && $job->task->id === $task->id;
        });
        
        $task->user->notify(new EnviarEmail($task));
        Notification::assertSentTo($task->user, EnviarEmail::class);
         
    }

    public function test_prevents_invalid_create_attempts()
    {
        $auth = $this->loginAndGetToken();
        $token = $auth['token'];
        $user = $auth['user'];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->json('post', '/api/tasks', [
                'id' => 1,
                'description' => 2231,
                'value' => 199,
                'user_id' => $user->id,
                'data' => '2024-09-08',
            ]);

        $response->assertStatus(422);
        
    }

    public function test_prevents_invalid_date_create_attempts()
    {
        $auth = $this->loginAndGetToken();
        $token = $auth['token'];
        $user = $auth['user'];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->json('post', '/api/tasks', [
                'id' => 1,
                'description' => 'teste',
                'value' => 199,
                'user_id' => $user->id,
                'data' => now()->subDay()->toDateString(),
            ]);

        $response->assertStatus(422);
    }

    public function test_prevents_invalid_value_create_attempts()
    {
        $auth = $this->loginAndGetToken();
        $token = $auth['token'];
        $user = $auth['user'];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->json('post', '/api/tasks', [
                'id' => 1,
                'description' => 'teste',
                'value' => 0,
                'user_id' => $user->id,
                'data' => now()->toDateString(),
            ]);

        $response->assertStatus(422);
    }

    public function  test_get_a_task()
    {
        $auth = $this->loginAndGetToken();
        $token = $auth['token'];

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->json('get', '/api/users');

        $response->assertStatus(200);
    }

    public function test_get_a_specific_task()
    {
        $auth = $this->loginAndGetToken();
        $token = $auth['token'];
        $loginResponse = $auth['response'];

        // Faz uma request GET para pegar uma task específica
        $taskResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->json('GET', '/api/tasks/1');

        $taskResponse->assertStatus(200)
            ->assertJsonStructure([
                'id',
                'description',
                'user',
                'value',
                'data',
                'createdAt',
                'updatedAt',
            ]);

        // Obtém o user_id da task
        $taskUserId = $taskResponse->json('user_id');

        // Verifica se o user_id da task é o mesmo do usuário logado
        $loggedInUserId = $loginResponse->json('data.user_id');

        if ($loggedInUserId !== $taskUserId) {
            abort(401, 'Unauthorized: You do not have access to this task.');
        }
    }

    public function test_update_a_task()
    {
        $auth = $this->loginAndGetToken();
        $token = $auth['token'];
        $user = $auth['user'];
        $loginResponse = $auth['response'];

        $task = Task::first();

        // Define os novos dados para a atualização do usuário
        if ($user->id === $task->user_id) {

            $updatedTaskData = [
                'description' => 'Teste Update',
                'value' => 10,
                'user_id' => 2,
                'data' => now()->toDateString()
            ];

            // Faz a request PUT para atualizar a Despesa
            $updateResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
                ->json('PUT', '/api/tasks/' . $task->id, $updatedTaskData);

            // Verifica se a resposta tem o status correto
            $updateResponse->assertStatus(200);

            // Verifica se os dados foram atualizados corretamente no banco de dados
            $this->assertDatabaseHas('tasks', [
                'id' => $task->id,
                'description' => 'Teste Update',
                'user_id' => 2,
                'value' => "10.00",
            ]);
        } else {
            $loginResponse->assertStatus(401);
        }
    }

    public function test_prevents_update_task_description_attempts()
    {
        $auth = $this->loginAndGetToken();
        $token = $auth['token'];
        $user = $auth['user'];
        $loginResponse = $auth['response'];

        $task = Task::first();

        // Define os novos dados para a atualização do usuário
        if ($user->id === $task->user_id) {

            $updatedTaskData = [
                'description' => 123123131,
                'value' => 10,
                'user_id' => 2,
                'data' => now()->toDateString()
            ];

            // Faz a request PUT para atualizar a Despesa
            $updateResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
                ->json('PUT', '/api/tasks/' . $task->id, $updatedTaskData);

            // Verifica se a resposta tem o status correto
            $updateResponse->assertStatus(422);
        } else {
            $loginResponse->assertStatus(401);
        }
    }

    public function test_prevents_update_task_value_attempts()
    {
        $auth = $this->loginAndGetToken();
        $token = $auth['token'];
        $user = $auth['user'];
        $loginResponse = $auth['response'];

        $task = Task::first();

        // Define os novos dados para a atualização do usuário
        if ($user->id === $task->user_id) {

            $updatedTaskData = [
                'description' => 'Teste',
                'value' => 0,
                'user_id' => 2,
                'data' => now()->toDateString()
            ];

            // Faz a request PUT para atualizar a Despesa
            $updateResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
                ->json('PUT', '/api/tasks/' . $task->id, $updatedTaskData);

            // Verifica se a resposta tem o status correto
            $updateResponse->assertStatus(422);
        } else {
            $loginResponse->assertStatus(401);
        }
    }

    public function test_prevents_update_task_data_attempts()
    {
        $auth = $this->loginAndGetToken();
        $token = $auth['token'];
        $user = $auth['user'];
        $loginResponse = $auth['response'];

        $task = Task::first();

        // Define os novos dados para a atualização do usuário
        if ($user->id === $task->user_id) {

            $updatedTaskData = [
                'description' => 'Teste',
                'value' => 10,
                'user_id' => 2,
                'data' => now()->subDay()->toDateString()
            ];

            // Faz a request PUT para atualizar a Despesa
            $updateResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
                ->json('PUT', '/api/tasks/' . $task->id, $updatedTaskData);

            // Verifica se a resposta tem o status correto
            $updateResponse->assertStatus(422);
        } else {
            $loginResponse->assertStatus(401);
        }
    }

    public function test_delete_a_task()
    {
        $auth = $this->loginAndGetToken();
        $token = $auth['token'];
        $loginResponse = $auth['response'];

        $task = Task::first();

        // Faz a request delete para excluir o usuário
        $deleteResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->json('DELETE', '/api/tasks/' . $task->id);

        // Verifica se a resposta tem o status correto
        $deleteResponse->assertStatus(200);

        // Obtém o user_id da task
        $taskUserId = $deleteResponse->json('user_id');

        // Verifica se o user_id da task é o mesmo do usuário logado
        $loggedInUserId = $loginResponse->json('data.user_id');

        if ($loggedInUserId !== $taskUserId) {
            $loginResponse->assertStatus(401);
        }

        // Verifica se a despesa foi removida do banco de dados
        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
        ]);
    }
}
