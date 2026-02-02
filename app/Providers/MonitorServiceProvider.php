<?php

namespace App\Providers;

use App\Contracts\CollectorInterface;
use App\Services\AlertService;
use App\Services\Collectors\DockerCollector;
use App\Services\Collectors\GaleraCollector;
use App\Services\Collectors\LaravelAppCollector;
use App\Services\Collectors\SynologyCollector;
use App\Services\MetricService;
use Illuminate\Support\ServiceProvider;

class MonitorServiceProvider extends ServiceProvider
{
    protected array $collectors = [
        SynologyCollector::class,
        DockerCollector::class,
        GaleraCollector::class,
        LaravelAppCollector::class,
    ];

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/monitor.php',
            'monitor'
        );

        // Register MetricService as singleton
        $this->app->singleton(MetricService::class);

        // Register AlertService as singleton
        $this->app->singleton(AlertService::class);

        // Register collectors
        foreach ($this->collectors as $collector) {
            $this->app->tag($collector, 'monitor.collectors');
        }
    }

    public function boot(): void
    {
        // Register middleware for Prometheus endpoint
        $this->app['router']->aliasMiddleware(
            'monitor.prometheus',
            \App\Http\Middleware\VerifyPrometheusToken::class
        );
    }

    /**
     * Get the appropriate collector for a node type.
     */
    public static function getCollectorFor(string $nodeType): ?CollectorInterface
    {
        $collectors = [
            SynologyCollector::class,
            DockerCollector::class,
            GaleraCollector::class,
            LaravelAppCollector::class,
        ];

        foreach ($collectors as $collectorClass) {
            if (in_array($nodeType, $collectorClass::supports())) {
                return app($collectorClass);
            }
        }

        return null;
    }
}
