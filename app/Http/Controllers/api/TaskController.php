<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use App\Notifications\RegisteredExpense;
use Illuminate\Http\Request;
use App\Models\Task;
use Illuminate\Database\QueryException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    public function index()
    {
      $tasks = Task::query()->with('user')->get();
      
      return TaskResource::collection($tasks);
    }


    public function store(Request $request)
    {
        try {
            $data = $request->all();
            Arr::set($data,'user_id', 1);

            $task = Task::create($data);
            $task->load('user');
            
            // auth()->user()->notify(new RegisteredExpense());

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
    public function show(string $id)
    {
        //
    }

  
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
