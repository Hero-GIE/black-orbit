<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ImageController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\VideoController;
use App\Http\Controllers\Api\AppInstallController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/health', function () {
    return response()->json(['status' => 'OK', 'timestamp' => now()]);
});

Route::post('/apps/install', [AppInstallController::class, 'track']);
Route::post('/apps/open',    [AppInstallController::class, 'trackOpen']);

Route::post('/login', [AuthController::class, 'login']);
Route::post('/refresh', [AuthController::class, 'refresh']);

Route::get('/articles', [ArticleController::class, 'index']);

Route::get('/articles/viewed', [ArticleController::class, 'viewed'])
    ->middleware('firebase.auth');

Route::get('/articles/{id}', [ArticleController::class, 'show']);

// Protected routes
Route::middleware(['firebase.auth'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);

    Route::post('/upload', [ImageController::class, 'upload']);
    Route::post('/upload-multiple', [ImageController::class, 'uploadMultiple']);
    Route::delete('/image', [ImageController::class, 'delete']);
    Route::get('/images', [ImageController::class, 'list']);
    Route::get('/image-url', [ImageController::class, 'getUrl']);

    Route::post('/articles/{id}/view', [ArticleController::class, 'recordView']);

    // Video Routes ---
    Route::get('/videos', [VideoController::class, 'fetchVideos']);
    Route::get('/videos/courses', [VideoController::class, 'fetchCourses']);
    Route::get('/videos/lessons', [VideoController::class, 'fetchLessons']);
    Route::post('/videos/upload', [VideoController::class, 'upload']);
    Route::post('/videos/{id}/replace', [VideoController::class, 'replaceVideo']);
    Route::get('/videos/{id}', [VideoController::class, 'getVideo']);
    Route::delete('/videos/{id}', [VideoController::class, 'destroy']);

    // App install / open stats
    Route::get('/apps/stats', [AppInstallController::class, 'stats']);
});
