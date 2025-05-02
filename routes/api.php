<?php

use App\Http\Controllers\API\ControllerApi;
use App\Http\Controllers\Api\ElementController;
use App\Http\Controllers\Api\VaultController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/sanctum/csrf-cookie', [ControllerApi::class, 'csrf']);
// Public routes
Route::post('/register', [ControllerApi::class, 'register'])->name('register');
Route::post('/login', [ControllerApi::class, 'login'])->name('login');

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::get('/me', [ControllerApi::class, 'me']);
    Route::post('/logout', [ControllerApi::class, 'logout'])->name('logout');
    Route::get('/allusers', [ControllerApi::class, 'allUsers'])->name('allusers');
    Route::put('/user/{id}', [ControllerApi::class, 'editUser'])->name('edituser');
    Route::delete('/user/{id}', [ControllerApi::class, 'deleteUser'])->name('deleteuser');

    Route::apiResource('vaults', VaultController::class);
    Route::apiResource('vaults.elements', ElementController::class)->shallow();
});