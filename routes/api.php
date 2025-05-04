<?php

use App\Http\Controllers\API\ControllerApi;
use App\Http\Controllers\Api\ElementController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\PasswordResetController;
use App\Http\Controllers\Api\VaultController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/sanctum/csrf-cookie', [ControllerApi::class, 'csrf']);
// Public routes
Route::post('/register', [ControllerApi::class, 'register'])->name('register');
Route::post('/login', [ControllerApi::class, 'login'])->name('login');

// Password reset routes
Route::prefix('password')->group(function () {
    Route::post('/forgot', [PasswordResetController::class, 'sendResetLink']);
    Route::post('/reset', [PasswordResetController::class, 'reset']);
    Route::post('/verify-code', [PasswordResetController::class, 'verifyCode']);
});

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // User profile routes
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [ControllerApi::class, 'logout'])->name('logout');
    // All users Route 
    Route::get('/allusers', [ControllerApi::class, 'allUsers'])->name('allusers');
    // Profile management routes (by admin)
    Route::get('/user/{id_profile}', [ControllerApi::class, 'getUser'])->name('getuser');
    Route::put('/user/{id_profile}', [ControllerApi::class, 'editUser'])->name('edituser');
    Route::delete('/user/{id_profile}', [ControllerApi::class, 'deleteUser'])->name('deleteuser');
    
    // User profile routes
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/', [ProfileController::class, 'update']);
        Route::post('/avatar', [ProfileController::class, 'updateAvatar']);
    });

    // Password change
    Route::post('/change-password', [ProfileController::class, 'changePassword']);

    // Vault routes
    Route::apiResource('vaults', VaultController::class);
    Route::apiResource('vaults.elements', ElementController::class)->shallow();
});