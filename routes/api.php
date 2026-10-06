<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PollController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicPollController;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Session\Middleware\StartSession;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::prefix('public/polls')
    ->middleware([EncryptCookies::class, AddQueuedCookiesToResponse::class, StartSession::class])
    ->group(function () {
        Route::get('/{poll:slug}', [PublicPollController::class, 'show']);
        Route::post('/{poll:slug}/vote', [PublicPollController::class, 'vote']);
    });

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/polls', [PollController::class, 'index']);
    Route::post('/polls', [PollController::class, 'store']);
});