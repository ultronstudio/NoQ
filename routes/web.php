<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PublicQueueController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\UsersController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])
    ->name('home');

Route::get('/q/{virtualQueue:public_id}', [PublicQueueController::class, 'show'])
    ->name('queue.public');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/users', [UsersController::class, 'index'])
        ->name('users.index')
        ->middleware('can:viewAny,'.User::class);

    Route::get('/queues/{virtualQueue}', [QueueController::class, 'show'])
        ->name('queue.show')
        ->middleware('can:view,virtualQueue');
});
