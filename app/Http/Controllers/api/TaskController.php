<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Resources\TaskResource;
use App\Jobs\EnviarEmailJob;
use App\Mail\CreatedTask;
use App\Models\Task;
use App\Notifications\EnviarEmail;
use App\Utils\MoneyMask;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;

class TaskController extends Controller
{
    public function index()
    {
        $tasks = Task::query()->with('user')->get();

        return TaskResource::collection($tasks);
    }


    public function store(StoreTaskRequest $request)
    {
        try {
            $data = $request->all();
            $task = Task::create($data);
            $task->load('user');

            $user = $request->user();
            EnviarEmailJob::dispatch($user, $task);

            return new TaskResource($task);
        } catch (\Exception $e) {
            if ($e instanceof QueryException) {
                $message = 'Erro ao cadastrar despesa.';
            }
            return response()->json([
                'message' => $message ?? $e->getMessage()
            ], 400);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int|string $id)
    {
        $data = Task::findOrFail($id);
        $data->load('user');
        if (Gate::denies('viewTask', $data)) {
            return response()->json(['message' => 'Usuário não tem permissão para acessar essa despesa'], 403);
        }

        return new TaskResource($data);
    }


    public function update(StoreTaskRequest $request, string $id)
    {
        $task = Task::find($id);
        
        if (Gate::denies('updateTask', $task)) {
            return response()->json(['message' => 'Usuário não tem permissão para editar essa despesa'], 403);
        }
        
        try {
            $data = $request->all();
           
            $task->description = Arr::get($data, 'description');
            $task->data = Arr::get($data, 'data');
            $task->value = Arr::get($data, 'value');
            $task->user_id = Arr::get($data, 'user_id');
            
            $task->save();

            $task->load('user');

            return new TaskResource($task);
        } catch (\Exception $e) {
            if ($e instanceof QueryException) {
                $message = 'Erro ao atualizar despesa.';
            }
            return response()->json([
                'message' => $message ?? $e->getMessage()
            ], 400);
        }
    }
    public function destroy(string $id)
    {
        $task = Task::find($id);

        if (Gate::denies('deleteTask', $task)) {
            return response()->json(['message' => 'Usuário não tem permissão para excluir essa despesa'], 403);
        }

        try {
            $task->delete();
            return response()->json(['message' => 'Despesa excluída com sucesso!']);
        } catch (\Exception $e) {
            if ($e instanceof QueryException) {
                $message = 'Erro ao deletar despesa.';
            }
            return response()->json([
                'message' => $message ?? $e->getMessage()
            ], 400);
        }
    }
}
