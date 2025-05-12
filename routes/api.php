<?php

use App\Http\Controllers\API\ControllerApi;
use App\Http\Controllers\Api\ElementController;
use App\Http\Controllers\API\ProfileController;
use App\Http\Controllers\API\PasswordResetController;
use App\Http\Controllers\Api\VaultController;
use App\Http\Controllers\PdfController;
use App\Http\Controllers\CollectionController;
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
    // User and profile routes
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::get('/user', function (Request $request) {
            return $request->user();
        });
        Route::put('/', [ProfileController::class, 'update']);
        Route::post('/avatar', [ProfileController::class, 'updateAvatar']);
        Route::post('/change-password', [ProfileController::class, 'changePassword']);
    });

    Route::post('/logout', [ControllerApi::class, 'logout'])->name('logout');
    
    // Admin routes for user management
    Route::get('/allusers', [ControllerApi::class, 'allUsers'])->name('allusers');
    Route::get('/user/{id_profile}', [ControllerApi::class, 'getUser'])->name('getuser');
    Route::put('/user/{id_profile}', [ControllerApi::class, 'editUser'])->name('edituser');
    Route::delete('/user/{id_profile}', [ControllerApi::class, 'deleteUser'])->name('deleteuser');

    // Vault routes
    Route::apiResource('vaults', VaultController::class);

    // Element routes
    Route::apiResource('vaults.elements', ElementController::class)->shallow();
    Route::put('elements/{elementId}/content', [ElementController::class, 'saveContent']);
    Route::post('/vaults/{vaultId}/search-tags', [ElementController::class, 'searchByTags'])->middleware('auth:sanctum');
    Route::post('/elements/{elementId}/restore-version', [ElementController::class, 'restoreVersion'])->middleware('auth:sanctum');

    // Protected PDF routes
    Route::get('/pdfs', [PdfController::class, 'index']);
    Route::post('/pdfs', [PdfController::class, 'store']);
    Route::get('/pdfs/{id}', [PdfController::class, 'show']);
    Route::put('/pdfs/{id}', [PdfController::class, 'update']);
    Route::delete('/pdfs/{id}', [PdfController::class, 'destroy']);
    Route::get('/pdfs/{id}/download', [PdfController::class, 'download']);
    Route::get('/pdfs/{id}/view', [PdfController::class, 'view']);
    Route::post('/collections/{collectionId}/pdfs/{pdfId}', [CollectionController::class, 'addPdf']);

    // Collection routes
    Route::get('/collections', [CollectionController::class, 'index']);
    Route::post('/collections', [CollectionController::class, 'store']);
    Route::get('/collections/{collectionId}', [CollectionController::class, 'show']);
    Route::put('/collections/{collectionId}', [CollectionController::class, 'update']);
    Route::delete('/collections/{collectionId}', [CollectionController::class, 'destroy']);
    Route::post('/collections/{collectionId}/pdfs/{pdfId}', [CollectionController::class, 'addPdf']);
    Route::delete('/collections/{collectionId}/pdfs/{pdfId}', [CollectionController::class, 'removePdf']);
    Route::get('/favorites', [CollectionController::class, 'getFavorites']);
});