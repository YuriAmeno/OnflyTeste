<?php

use App\Http\Controllers\api\TaskController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;



Route::post('/login', [AuthController::class, 'login'])
    ->name('login');

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware(['auth:sanctum']);

Route::get('verify_token', [AuthController::class, 'verifyToken'])
    ->middleware('auth:sanctum');

Route::post('/first-login', [UserController::class, 'firstLogin']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::resource('/users', UserController::class);
    Route::resource('/tasks', TaskController::class);
});
