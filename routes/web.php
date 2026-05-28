<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstructionController;
use App\Http\Controllers\OpenRouterDebugController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/debug/openrouter', [OpenRouterDebugController::class, 'create'])->name('debug.openrouter');
    Route::post('/debug/openrouter', [OpenRouterDebugController::class, 'store'])->name('debug.openrouter.store');
    Route::post('/instructions', [InstructionController::class, 'store'])->name('instructions.store');
    Route::post('/instructions/voice', [InstructionController::class, 'storeVoice'])->name('instructions.voice.store');
    Route::get('/instructions/{instruction}', [InstructionController::class, 'show'])->name('instructions.show');
    Route::post('/instructions/{instruction}/retry', [InstructionController::class, 'retry'])->name('instructions.retry');
});
