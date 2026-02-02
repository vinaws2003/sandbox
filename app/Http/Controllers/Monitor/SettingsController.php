<?php

namespace App\Http\Controllers\Monitor;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Monitor/Settings', [
            'settings' => [
                'polling_interval' => config('monitor.polling_interval'),
                'retention_days' => config('monitor.retention_days'),
                'synology_timeout' => config('monitor.synology.timeout'),
                'docker_timeout' => config('monitor.docker.timeout'),
                'galera_timeout' => config('monitor.galera.timeout'),
                'laravel_app_timeout' => config('monitor.laravel_app.timeout'),
                'prometheus_enabled' => config('monitor.prometheus.enabled'),
                'alert_cooldown_default' => config('monitor.alert_cooldown_default'),
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        // Settings are managed via .env file
        // This controller provides a read-only view
        // In a production app, you might write to a settings table

        return redirect()
            ->route('monitor.settings')
            ->with('info', 'Settings are managed via environment variables. Please update your .env file.');
    }
}
