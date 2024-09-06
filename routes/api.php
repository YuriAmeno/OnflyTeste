<?php

use App\Http\Controllers\api\TaskController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::resource('/users', UserController::class);
Route::resource('/tasks', TaskController::class);

Route::post('login', [AuthController::class, 'login'])
    ->name('login');

Route::post('logout', [AuthController::class, 'logout'])
    ->middleware(['auth:sanctum']);
