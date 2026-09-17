<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ImageController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ArticleController;

// Public routes (no authentication needed)
Route::get('/health', function () {
    return response()->json(['status' => 'OK', 'timestamp' => now()]);
});

Route::post('/login', [AuthController::class, 'login']);
Route::post('/refresh', [AuthController::class, 'refresh']);
Route::get('/articles', [ArticleController::class, 'index']);
Route::get('/articles/{id}', [ArticleController::class, 'show']);

// Protected routes
Route::middleware(['firebase.auth'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/upload', [ImageController::class, 'upload']);
    Route::post('/upload-multiple', [ImageController::class, 'uploadMultiple']);
    Route::delete('/image', [ImageController::class, 'delete']);
    Route::get('/images', [ImageController::class, 'list']);
    Route::get('/image-url', [ImageController::class, 'getUrl']);
});
