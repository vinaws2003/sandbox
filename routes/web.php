<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Tools\Base64Controller;
use App\Http\Controllers\Tools\CalculatorController;
use App\Http\Controllers\Tools\ColorPickerController;
use App\Http\Controllers\Tools\JsonFormatterController;
use App\Http\Controllers\Tools\TextCaseController;
use App\Http\Controllers\Tools\UuidGeneratorController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Tools routes
    Route::prefix('tools')->name('tools.')->group(function () {
        Route::get('/calculator', [CalculatorController::class, 'index'])->name('calculator');
        Route::get('/text-case', [TextCaseController::class, 'index'])->name('text-case');
        Route::get('/json-formatter', [JsonFormatterController::class, 'index'])->name('json-formatter');
        Route::get('/color-picker', [ColorPickerController::class, 'index'])->name('color-picker');
        Route::get('/uuid-generator', [UuidGeneratorController::class, 'index'])->name('uuid-generator');
        Route::get('/base64', [Base64Controller::class, 'index'])->name('base64');
    });
});

require __DIR__.'/auth.php';
require __DIR__.'/monitor.php';
