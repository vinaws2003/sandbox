# NAS Monitor Setup Guide

This guide walks you through setting up the NAS Monitor to track your infrastructure.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Initial Setup](#initial-setup)
3. [Configuration](#configuration)
4. [Adding Nodes](#adding-nodes)
5. [Setting Up Alerts](#setting-up-alerts)
6. [Running the Collector](#running-the-collector)
7. [Prometheus Integration](#prometheus-integration)
8. [Troubleshooting](#troubleshooting)

---

## Prerequisites

- Laravel application running with database configured
- Access to the systems you want to monitor:
  - Synology NAS: Admin credentials with API access
  - Docker: Docker API exposed (TCP port 2375 or SSH)
  - Galera: MySQL user with PROCESS privilege
  - Laravel Apps: `/health` endpoint implemented

---

## Initial Setup

### 1. Run Migrations

```bash
php artisan migrate
```

This creates the required tables:
- `nodes` - Monitored endpoints
- `metrics` - Time-series data
- `alerts` - Alert rules
- `alert_logs` - Alert history

### 2. (Optional) Seed Sample Data

For testing purposes, you can seed sample data:

```bash
php artisan db:seed --class=MonitorSeeder
```

### 3. Build Frontend Assets

```bash
npm run build
# or for development
npm run dev
```

### 4. Access the Monitor

Navigate to `/monitor` in your browser (requires authentication).

---

## Configuration

Add these environment variables to your `.env` file:

```env
# Polling interval in seconds (how often to collect metrics)
MONITOR_POLLING_INTERVAL=60

# How many days to keep metric data
MONITOR_RETENTION_DAYS=7

# Synology API settings
SYNOLOGY_API_VERSION=7
SYNOLOGY_TIMEOUT=30

# Docker API timeout
DOCKER_TIMEOUT=10

# Galera/MySQL timeout
GALERA_TIMEOUT=5

# Laravel app health check settings
LARAVEL_APP_TIMEOUT=10
LARAVEL_APP_RETRIES=2

# Prometheus metrics endpoint
PROMETHEUS_ENABLED=true
PROMETHEUS_TOKEN=your-secret-token  # Optional, leave empty for no auth

# Default alert cooldown in minutes
ALERT_COOLDOWN_DEFAULT=15
```

---

## Adding Nodes

### Via Web Interface

1. Go to `/monitor`
2. Click **"Add Node"**
3. Fill in the form and save

### Via Tinker (CLI)

```bash
php artisan tinker
```

#### Synology NAS

```php
App\Models\Node::create([
    'name' => 'NAS Primary',
    'type' => 'synology',
    'host' => '192.168.1.100',
    'port' => 5000,  // 5000 for HTTP, 5001 for HTTPS
    'credentials' => [
        'username' => 'admin',
        'password' => 'your-password',
    ],
    'is_active' => true,
]);
```

**Synology Requirements:**
- Create a dedicated admin user for monitoring
- Enable "DSM" in Control Panel > Application Portal
- For HTTPS, use port 5001

**Collected Metrics:**
| Metric | Description |
|--------|-------------|
| `cpu` | CPU usage percentage |
| `memory` | RAM usage percentage |
| `disk` | Disk usage percentage (per volume) |
| `network_in` | Incoming network traffic (KB/s) |
| `network_out` | Outgoing network traffic (KB/s) |
| `temperature` | System temperature (°C) |
| `uptime` | System uptime (seconds) |

---

#### Docker Host

```php
App\Models\Node::create([
    'name' => 'Docker Server',
    'type' => 'docker',
    'host' => '192.168.1.50',
    'port' => 2375,
    'credentials' => null,
    'is_active' => true,
]);
```

**Docker Requirements:**
- Docker API must be exposed on TCP port
- Edit `/etc/docker/daemon.json`:
  ```json
  {
    "hosts": ["unix:///var/run/docker.sock", "tcp://0.0.0.0:2375"]
  }
  ```
- Restart Docker: `sudo systemctl restart docker`

> **Security Warning:** Exposing Docker API without TLS is insecure. For production, configure TLS certificates.

**Collected Metrics:**
| Metric | Description |
|--------|-------------|
| `container_status` | Running status (1=running, 0=stopped) |
| `container_cpu` | Container CPU usage percentage |
| `container_memory` | Container memory usage percentage |
| `container_restart_count` | Number of container restarts |

---

#### Galera Cluster Node

```php
App\Models\Node::create([
    'name' => 'Galera Node 1',
    'type' => 'galera',
    'host' => '192.168.1.110',
    'port' => 3306,
    'credentials' => [
        'username' => 'monitor',
        'password' => 'monitor-password',
        'database' => 'mysql',
    ],
    'is_active' => true,
]);
```

**MySQL User Setup:**

```sql
CREATE USER 'monitor'@'%' IDENTIFIED BY 'monitor-password';
GRANT PROCESS ON *.* TO 'monitor'@'%';
FLUSH PRIVILEGES;
```

**Collected Metrics:**
| Metric | Description |
|--------|-------------|
| `wsrep_cluster_size` | Number of nodes in cluster |
| `wsrep_cluster_status` | Primary (1) or Non-Primary (0) |
| `wsrep_ready` | Node ready to accept queries (1=yes) |
| `wsrep_connected` | Node connected to cluster (1=yes) |
| `wsrep_local_state` | Sync state (4=Synced, 2=Donor, etc.) |
| `wsrep_flow_control_paused` | Flow control pause ratio |

---

#### Laravel Application

```php
App\Models\Node::create([
    'name' => 'API Server',
    'type' => 'laravel_app',
    'host' => 'api.example.com',
    'port' => 443,  // 443 for HTTPS, 80 for HTTP
    'credentials' => [
        'health_endpoint' => '/health',
    ],
    'is_active' => true,
]);
```

**Health Endpoint Implementation:**

Add this to your Laravel app's `routes/web.php`:

```php
Route::get('/health', function () {
    $start = microtime(true);

    $checks = [
        'database' => false,
        'cache' => false,
        'queue' => ['size' => 0, 'failed' => 0],
    ];

    try {
        DB::connection()->getPdo();
        $checks['database'] = true;
    } catch (\Exception $e) {}

    try {
        Cache::get('health-check');
        $checks['cache'] = true;
    } catch (\Exception $e) {}

    try {
        $checks['queue'] = [
            'size' => Queue::size(),
            'failed' => DB::table('failed_jobs')->count(),
        ];
    } catch (\Exception $e) {}

    $status = !$checks['database'] ? 'unhealthy'
        : (!$checks['cache'] ? 'degraded' : 'healthy');

    return response()->json([
        'status' => $status,
        'checks' => $checks,
        'response_time_ms' => round((microtime(true) - $start) * 1000, 2),
    ], $status === 'unhealthy' ? 503 : 200);
});
```

**Collected Metrics:**
| Metric | Description |
|--------|-------------|
| `response_time` | Health check response time (seconds) |
| `status` | Health status (1=healthy, 0.5=degraded, 0=unhealthy) |
| `queue_size` | Number of jobs in queue |
| `failed_jobs` | Number of failed jobs |
| `database_connected` | Database connection status |
| `cache_connected` | Cache connection status |

---

## Setting Up Alerts

### Via Web Interface

1. Go to `/monitor/alerts`
2. Click **"Add Alert"**
3. Configure the alert rule

### Via Tinker

```php
// Alert when CPU exceeds 80% on any node
App\Models\Alert::create([
    'node_id' => null,  // null = all nodes
    'metric_type' => 'cpu',
    'condition' => 'gt',  // gt, gte, lt, lte, eq, neq
    'threshold' => 80,
    'notification_channel' => 'database',  // mail, slack, database
    'notification_target' => null,
    'cooldown_minutes' => 15,
    'is_active' => true,
]);

// Alert when specific node disk usage exceeds 85%
App\Models\Alert::create([
    'node_id' => 1,  // Specific node ID
    'metric_type' => 'disk',
    'condition' => 'gt',
    'threshold' => 85,
    'notification_channel' => 'mail',
    'notification_target' => 'admin@example.com',
    'cooldown_minutes' => 30,
    'is_active' => true,
]);

// Alert when Galera cluster size drops below 3
App\Models\Alert::create([
    'node_id' => null,
    'metric_type' => 'wsrep_cluster_size',
    'condition' => 'lt',
    'threshold' => 3,
    'notification_channel' => 'slack',
    'notification_target' => '#infrastructure-alerts',
    'cooldown_minutes' => 5,
    'is_active' => true,
]);
```

### Condition Reference

| Condition | Meaning |
|-----------|---------|
| `gt` | Greater than |
| `gte` | Greater than or equal |
| `lt` | Less than |
| `lte` | Less than or equal |
| `eq` | Equal to |
| `neq` | Not equal to |

### Notification Channels

| Channel | Target Format | Requirements |
|---------|---------------|--------------|
| `mail` | Email address | Mail configured in Laravel |
| `slack` | Channel name (#channel) | Slack webhook configured |
| `database` | null | Logs to alert_logs table |

---

## Running the Collector

### Manual Collection

```bash
# Collect from all active nodes
php artisan monitor:collect

# Collect from specific node
php artisan monitor:collect --node=1

# Collect from specific type
php artisan monitor:collect --type=synology
```

### Check Alerts Manually

```bash
php artisan monitor:check-alerts
```

### Cleanup Old Metrics

```bash
# Preview what would be deleted
php artisan monitor:cleanup --dry-run

# Actually delete old metrics
php artisan monitor:cleanup

# Override retention days
php artisan monitor:cleanup --days=3
```

### Automated Collection (Scheduler)

The scheduler is already configured. Run it with:

```bash
# Development (foreground)
php artisan schedule:work

# Production (cron job)
# Add to crontab: crontab -e
* * * * * cd /path/to/project && php artisan schedule:run >> /dev/null 2>&1
```

**Scheduled Tasks:**
- `monitor:collect` - Every minute
- `monitor:check-alerts` - Every minute
- `monitor:cleanup` - Daily

### Using Supervisor (Recommended for Production)

Create `/etc/supervisor/conf.d/monitor-scheduler.conf`:

```ini
[program:monitor-scheduler]
process_name=%(program_name)s
command=php /path/to/project/artisan schedule:work
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/project/storage/logs/scheduler.log
```

Then:
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start monitor-scheduler
```

---

## Prometheus Integration

The monitor exposes a Prometheus-compatible `/metrics` endpoint.

### Configure Prometheus

Add to your `prometheus.yml`:

```yaml
scrape_configs:
  - job_name: 'nas-monitor'
    scrape_interval: 60s
    static_configs:
      - targets: ['your-laravel-app.com']
    # If using token authentication:
    bearer_token: 'your-prometheus-token'
```

### Available Metrics

```
# NAS metrics
nas_cpu_usage{node="NAS-1",type="synology",host="192.168.1.100"}
nas_memory_usage{node="NAS-1",type="synology",host="192.168.1.100"}
nas_disk_usage{node="NAS-1",type="synology",host="192.168.1.100",volume="volume1"}

# Galera metrics
galera_cluster_size{node="Galera-1",type="galera",host="192.168.1.110"}
galera_ready{node="Galera-1",type="galera",host="192.168.1.110"}

# Laravel app metrics
laravel_response_time{node="API",type="laravel_app",host="api.example.com"}
laravel_status{node="API",type="laravel_app",host="api.example.com"}

# Docker metrics
docker_container_status{node="Docker-1",type="docker",host="192.168.1.50",container="nginx"}
docker_container_cpu{node="Docker-1",type="docker",host="192.168.1.50",container="nginx"}
```

### Securing the Endpoint

Set `PROMETHEUS_TOKEN` in `.env`:

```env
PROMETHEUS_TOKEN=your-secret-token
```

Access with:
- Header: `Authorization: Bearer your-secret-token`
- Query param: `/metrics?token=your-secret-token`

---

## Troubleshooting

### Test Node Connection

```bash
php artisan tinker
```

```php
$node = App\Models\Node::find(1);
$collector = App\Providers\MonitorServiceProvider::getCollectorFor($node->type);
$collector->testConnection($node);  // Returns true/false
```

### View Collected Metrics

```php
// Latest metrics for a node
$node = App\Models\Node::find(1);
$metrics = app(App\Services\MetricService::class)->getLatestMetrics($node);
$metrics->pluck('value', 'type');
```

### Check Logs

```bash
# Laravel logs
tail -f storage/logs/laravel.log

# Real-time log viewer
php artisan pail
```

### Common Issues

#### Synology: "Connection refused"
- Check firewall settings on NAS
- Verify port (5000 HTTP / 5001 HTTPS)
- Ensure DSM is enabled in Application Portal

#### Synology: "Authentication failed"
- Verify username/password
- Check if 2FA is enabled (not supported)
- Try creating a new admin user

#### Docker: "Connection refused"
- Ensure Docker API is exposed: `docker -H tcp://localhost:2375 info`
- Check firewall rules

#### Galera: "Access denied"
- Verify MySQL user has PROCESS privilege
- Check if user can connect from monitor host

#### Laravel App: "Connection timed out"
- Verify the app is accessible from the monitor server
- Check if `/health` endpoint exists
- Increase `LARAVEL_APP_TIMEOUT` if needed

### Reset and Start Fresh

```bash
# Clear all monitor data
php artisan tinker
>>> App\Models\Metric::truncate();
>>> App\Models\AlertLog::truncate();
>>> App\Models\Alert::truncate();
>>> App\Models\Node::truncate();

# Re-seed sample data
php artisan db:seed --class=MonitorSeeder
```

---

## Quick Start Checklist

- [ ] Run migrations: `php artisan migrate`
- [ ] Configure `.env` variables
- [ ] Add at least one node via `/monitor/nodes/create`
- [ ] Test connection using the "Test Connection" button
- [ ] Run manual collection: `php artisan monitor:collect`
- [ ] Verify metrics appear in dashboard
- [ ] Set up alerts for critical thresholds
- [ ] Start scheduler: `php artisan schedule:work`
- [ ] (Optional) Configure Prometheus scraping
