# Health Endpoint Specification

This document describes the health endpoint that Laravel applications should implement to be monitored by the NAS Monitor system.

## Endpoint

```
GET /health
```

## Response Format

The endpoint should return a JSON response with the following structure:

```json
{
  "status": "healthy|degraded|unhealthy",
  "checks": {
    "database": true,
    "cache": true,
    "queue": {
      "size": 5,
      "failed": 0
    }
  },
  "response_time_ms": 45
}
```

## Field Descriptions

### status (required)
- `healthy` - All systems operating normally
- `degraded` - Some non-critical systems may have issues
- `unhealthy` - Critical systems are failing

### checks (required)
An object containing the status of various subsystems:

| Check | Type | Description |
|-------|------|-------------|
| `database` | boolean | Database connection is working |
| `cache` | boolean | Cache connection is working |
| `queue` | object | Queue status with `size` and `failed` counts |

### response_time_ms (optional)
The internal response time in milliseconds for processing the health check.

## Example Implementation

### Using Laravel's Health Facade (Laravel 10+)

```php
// routes/web.php or routes/api.php
Route::get('/health', function () {
    $startTime = microtime(true);

    $checks = [
        'database' => false,
        'cache' => false,
        'queue' => ['size' => 0, 'failed' => 0],
    ];

    // Check database
    try {
        DB::connection()->getPdo();
        $checks['database'] = true;
    } catch (\Exception $e) {
        $checks['database'] = false;
    }

    // Check cache
    try {
        Cache::store()->get('health-check');
        $checks['cache'] = true;
    } catch (\Exception $e) {
        $checks['cache'] = false;
    }

    // Check queue
    try {
        $checks['queue'] = [
            'size' => Queue::size(),
            'failed' => DB::table('failed_jobs')->count(),
        ];
    } catch (\Exception $e) {
        // Queue check failed
    }

    // Determine overall status
    $status = 'healthy';
    if (!$checks['database']) {
        $status = 'unhealthy';
    } elseif (!$checks['cache'] || $checks['queue']['failed'] > 10) {
        $status = 'degraded';
    }

    $responseTimeMs = (microtime(true) - $startTime) * 1000;

    return response()->json([
        'status' => $status,
        'checks' => $checks,
        'response_time_ms' => round($responseTimeMs, 2),
    ]);
});
```

### Using a Dedicated Controller

```php
// app/Http/Controllers/HealthController.php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;

class HealthController extends Controller
{
    public function __invoke()
    {
        $startTime = microtime(true);

        $checks = $this->runChecks();
        $status = $this->determineStatus($checks);

        return response()->json([
            'status' => $status,
            'checks' => $checks,
            'response_time_ms' => round((microtime(true) - $startTime) * 1000, 2),
        ]);
    }

    protected function runChecks(): array
    {
        return [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
        ];
    }

    protected function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function checkCache(): bool
    {
        try {
            Cache::store()->get('health-check-' . time());
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function checkQueue(): array
    {
        try {
            return [
                'size' => Queue::size(),
                'failed' => DB::table('failed_jobs')->count(),
            ];
        } catch (\Exception $e) {
            return ['size' => 0, 'failed' => 0];
        }
    }

    protected function determineStatus(array $checks): string
    {
        if (!$checks['database']) {
            return 'unhealthy';
        }

        if (!$checks['cache'] || $checks['queue']['failed'] > 10) {
            return 'degraded';
        }

        return 'healthy';
    }
}
```

## HTTP Status Codes

The endpoint should return appropriate HTTP status codes:

| Status | HTTP Code |
|--------|-----------|
| healthy | 200 |
| degraded | 200 |
| unhealthy | 503 |

## Security Considerations

1. **No Authentication Required**: The health endpoint should be publicly accessible for monitoring systems.

2. **Rate Limiting**: Consider applying rate limiting to prevent abuse:
   ```php
   Route::middleware('throttle:60,1')->get('/health', HealthController::class);
   ```

3. **Minimal Information**: Don't expose sensitive information in the health response.

4. **Fast Response**: The health check should complete quickly (< 1 second).

## Monitoring Integration

When configuring the monitor, set the health endpoint path in the node credentials:

```json
{
  "health_endpoint": "/health"
}
```

The monitor will:
1. Make an HTTP GET request to `https://{host}/health`
2. Parse the JSON response
3. Extract metrics: `response_time`, `status`, `queue_size`, `failed_jobs`, `database_connected`, `cache_connected`
4. Store metrics for visualization and alerting

## Recommended Alerts

| Metric | Condition | Threshold | Description |
|--------|-----------|-----------|-------------|
| `response_time` | Greater than | 1.0s | Slow health check response |
| `status` | Less than | 1 | App is degraded or unhealthy |
| `queue_size` | Greater than | 100 | Queue is backing up |
| `failed_jobs` | Greater than | 10 | Too many failed jobs |
