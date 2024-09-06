<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
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


    public function store(Request $request)
    {
        try {

            $request->validate([
                "name"=> ['required', 'string', 'max:64'],
                "email"=>  ['required', 'email', 'unique:users', 'max:255'],
                "password"=> ['required', Password::defaults()],
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
