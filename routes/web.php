<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\EnrollmentController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\CosmicWordSearchController;
use App\Http\Controllers\Admin\ChatController;
use App\Http\Controllers\Admin\PersonalityController;
use App\Http\Controllers\Admin\PlayerController;
use App\Http\Controllers\Api\ImageController;

    Route::get('/', function () {
    return redirect('/login');
    });

    // Auth Routes
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');

    Route::get('/forgot-password', function () {
    return view('auth.forgot-password');
    })->name('password.request');

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    // Redirect /dashboard to /admin/dashboard
    Route::get('/dashboard', function () {
    return redirect('/admin/dashboard');
    })->middleware('auth')->name('dashboard.redirect');

    // Admin Routes (Protected)
    Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {

    Route::post('/api/images/upload', [ImageController::class, 'upload']);

    // admin dashboard routes
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/api/dashboard/stats', [DashboardController::class, 'getStats'])->name('api.stats');
    Route::get('/api/analytics', [DashboardController::class, 'getAnalytics'])->name('api.analytics');
    Route::get('/api/distribution', [DashboardController::class, 'getDistribution'])->name('api.distribution');
    Route::get('/api/dashboard/activity', [DashboardController::class, 'getActivity'])->name('api.activity');

    // user routes
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/api/users', [UserController::class, 'fetchUsers'])->name('api.users');
    Route::get('/api/users/{id}', [UserController::class, 'getUser'])->name('api.user.get');
    Route::post('/api/users', [UserController::class, 'store'])->name('api.users.store');
    Route::put('/api/users/{id}', [UserController::class, 'update'])->name('api.users.update');
    Route::delete('/api/users/{id}', [UserController::class, 'destroy'])->name('api.users.delete');

    // enrollment routes
    Route::get('/enrollments', [EnrollmentController::class, 'index'])->name('enrollments.index');
    Route::get('/api/enrollments', [EnrollmentController::class, 'fetchEnrollments'])->name('api.enrollments');

    // course routes
    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
    Route::get('/api/courses', [CourseController::class, 'fetchCourses'])->name('api.courses');
    Route::get('/api/courses/{id}', [CourseController::class, 'getCourse'])->name('api.course.get');
    Route::post('/api/courses', [CourseController::class, 'store'])->name('api.courses.store');
    Route::put('/api/courses/{id}', [CourseController::class, 'update'])->name('api.courses.update');
    Route::delete('/api/courses/{id}', [CourseController::class, 'destroy'])->name('api.courses.delete');

    // lesson routes
    Route::get('/courses/{courseId}/lessons', [CourseController::class, 'showLessons'])->name('courses.lessons');
    Route::get('/api/courses/{courseId}/lessons', [CourseController::class, 'fetchLessons'])->name('api.lessons.fetch');
    Route::get('/api/courses/{courseId}/lessons/{lessonId}', [CourseController::class, 'getLesson'])->name('api.lesson.get');
    Route::post('/api/lessons', [CourseController::class, 'storeLesson'])->name('api.lessons.store');
    Route::put('/api/courses/{courseId}/lessons/{lessonId}', [CourseController::class, 'updateLesson'])->name('api.lessons.update');
    Route::delete('/api/courses/{courseId}/lessons/{lessonId}', [CourseController::class, 'destroyLesson'])->name('api.lessons.delete');

    // cosmic word search routes
    Route::get('/cosmic', [CosmicWordSearchController::class, 'index'])->name('cosmic.index');
    Route::get('/api/cosmic/progress', [CosmicWordSearchController::class, 'fetchProgress'])->name('api.cosmic.progress');
    Route::get('/api/cosmic/progress/{userId}', [CosmicWordSearchController::class, 'getUserProgress'])->name('api.cosmic.user');

    // chat routes
    Route::get('/chats', [ChatController::class, 'index'])->name('chats.index');
    Route::get('/api/chats', [ChatController::class, 'fetchChats'])->name('api.chats');
    Route::get('/api/chats/{id}', [ChatController::class, 'getChat'])->name('api.chat.get');

    // Personality Routes
    Route::get('/personalities', [PersonalityController::class, 'index'])->name('personalities.index');
    Route::get('/api/personalities', [PersonalityController::class, 'fetchPersonalities'])->name('api.personalities');
    Route::get('/api/personalities/{id}', [PersonalityController::class, 'getPersonality'])->name('api.personality.get');
    Route::post('/api/personalities', [PersonalityController::class, 'store'])->name('api.personalities.store');
    Route::put('/api/personalities/{id}', [PersonalityController::class, 'update'])->name('api.personalities.update');
    Route::delete('/api/personalities/{id}', [PersonalityController::class, 'destroy'])->name('api.personalities.delete');

    // Player Routes
    Route::get('/players', [PlayerController::class, 'index'])->name('players.index');
    Route::get('/api/players', [PlayerController::class, 'fetchPlayers'])->name('api.players');
    Route::get('/api/players/{id}', [PlayerController::class, 'getPlayer'])->name('api.player.get');
    Route::put('/api/players/{id}', [PlayerController::class, 'update'])->name('api.players.update');
    Route::delete('/api/players/{id}', [PlayerController::class, 'destroy'])->name('api.players.delete');

});
