<?php

use App\Http\Controllers\Monitor\AlertController;
use App\Http\Controllers\Monitor\AlertHistoryController;
use App\Http\Controllers\Monitor\DashboardController;
use App\Http\Controllers\Monitor\GaleraClusterController;
use App\Http\Controllers\Monitor\NodeController;
use App\Http\Controllers\Monitor\PrometheusController;
use App\Http\Controllers\Monitor\SettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Monitor Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->prefix('monitor')->name('monitor.')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Nodes
    Route::resource('nodes', NodeController::class);
    Route::post('nodes/{node}/test', [NodeController::class, 'testConnection'])->name('nodes.test');

    // Galera Cluster
    Route::get('galera', [GaleraClusterController::class, 'index'])->name('galera');

    // Alerts
    Route::resource('alerts', AlertController::class)->except(['show']);
    Route::get('alerts-history', [AlertHistoryController::class, 'index'])->name('alerts.history');

    // Settings
    Route::get('settings', [SettingsController::class, 'index'])->name('settings');
    Route::put('settings', [SettingsController::class, 'update'])->name('settings.update');
});

// Prometheus endpoint (outside auth middleware)
Route::get('metrics', [PrometheusController::class, 'index'])
    ->middleware('monitor.prometheus')
    ->name('metrics.prometheus');
