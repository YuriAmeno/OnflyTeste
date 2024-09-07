<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index()
    {
        $users = User::query()->get();
      
        return UserResource::collection($users);
    }

    public function firstLogin(Request $request){
        try {
            $request->validate([
                "name" => ['required', 'string', 'max:64'],
                "email" =>  ['required', 'email', 'unique:users', 'max:255'],
                "password" => ['required', 'string', 'min:8'],
            ]);

            $data = $request->all();
            Arr::set($data,'password', Hash::make($data['password']));

            $task = User::create($data);
    
            return new UserResource($task);
        } catch (\Exception $e) {
            if ($e instanceof QueryException) {
                $message = 'Erro ao cadastrar usúario.';
            }
            return response()->json([
                'message' => $message ?? $e->getMessage()
            ], 400);
        }
    }


    public function store(StoreUserRequest $request)
    {
        try {
            
            $data = $request->all();
            Arr::set($data,'password', Hash::make($data['password']));

            $task = User::create($data);
    
            return new UserResource($task);
        } catch (\Exception $e) {
            if ($e instanceof QueryException) {
                $message = 'Erro ao cadastrar usúario.';
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
        $data = User::findOrFail($id);

        return new UserResource($data);
    }

  
    public function update(Request $request, string $id)
    {
        try {
            $request->validate([
                "name" => ['required', 'string', 'max:64'],
                "email" =>  ['sometimes', 'email', 'unique:users', 'max:255'],
            ]);
            
            $data = $request->all();

            $user = User::find($id);

            $user->name = Arr::get($data, 'name');
            $user->email = Arr::get($data, 'email');
            $user->save();

            return new UserResource($user);
        } catch (\Exception $e) {
            if ($e instanceof QueryException) {
                $message = 'Erro ao atualizar despesa.';
            }
            return response()->json([
                'message' => $message ?? $e->getMessage()
            ], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::find($id);

        try {
            $user->delete();
            return response()->json(['message' => 'Usuário excluído com sucesso!']);
        } catch (\Exception $e) {
            if ($e instanceof QueryException) {
                $message = 'Erro ao deletar usuário.';
            }
            return response()->json([
                'message' => $message ?? $e->getMessage()
            ], 400);
        }
    }
}
