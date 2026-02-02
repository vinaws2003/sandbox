# Sandbox - Laravel 12 Application

## Project Overview

A Laravel 12 application with Inertia.js + Vue 3 frontend, featuring developer tools and infrastructure monitoring capabilities.

## Tech Stack

- **Backend**: Laravel 12, PHP 8.2+
- **Frontend**: Vue 3 (Composition API), Inertia.js, Tailwind CSS
- **Database**: MySQL (sandbox database on 127.0.0.1:3306)
- **Authentication**: Laravel Breeze (Inertia/Vue stack)
- **Charts**: Chart.js with vue-chartjs

## Directory Structure

```
app/
├── Console/Commands/          # Artisan commands
├── Contracts/                 # Interfaces
├── Exceptions/                # Custom exceptions
├── Http/
│   ├── Controllers/
│   │   ├── Auth/              # Authentication controllers
│   │   ├── Monitor/           # NAS Monitor controllers
│   │   └── Tools/             # Developer tools controllers
│   ├── Middleware/
│   └── Requests/Monitor/      # Form request validators
├── Models/                    # Eloquent models
├── Notifications/             # Mail/Slack notifications
├── Providers/
└── Services/
    └── Collectors/            # Metric collectors

resources/js/
├── Components/
│   └── Monitor/               # Monitor-specific components
├── Layouts/
│   ├── AuthenticatedLayout.vue
│   └── GuestLayout.vue
└── Pages/
    ├── Auth/
    ├── Monitor/               # Monitor pages
    │   ├── Alerts/
    │   └── Nodes/
    └── Tools/

routes/
├── web.php                    # Main routes
├── auth.php                   # Authentication routes
├── monitor.php                # Monitor routes
└── console.php                # Scheduled commands
```

## Features

### Developer Tools (`/tools/*`)
- Calculator
- Text Case Converter
- JSON Formatter
- Color Picker
- UUID Generator
- Base64 Encoder/Decoder

### NAS Monitor (`/monitor/*`)

Infrastructure monitoring system for:
- **Synology NAS** - CPU, memory, disk, temperature, network
- **Docker Hosts** - Container status, CPU, memory, restart counts
- **Galera Cluster** - Cluster size, sync status, flow control
- **Laravel Applications** - Response time, health status, queue metrics

#### Monitor Routes

| Route | Description |
|-------|-------------|
| `/monitor` | Dashboard with node overview |
| `/monitor/nodes` | Node management (CRUD) |
| `/monitor/nodes/{id}` | Node detail with charts |
| `/monitor/galera` | Galera cluster visualization |
| `/monitor/alerts` | Alert rule management |
| `/monitor/alerts-history` | Alert history log |
| `/monitor/settings` | Configuration view |
| `/metrics` | Prometheus endpoint |

#### Artisan Commands

```bash
# Collect metrics from all active nodes
php artisan monitor:collect

# Check alerts against current metrics
php artisan monitor:check-alerts

# Clean up old metrics (respects retention_days config)
php artisan monitor:cleanup
php artisan monitor:cleanup --dry-run
```

#### Database Tables

- `nodes` - Monitored endpoints (host, type, credentials)
- `metrics` - Time-series metric data
- `alerts` - Alert rules with thresholds
- `alert_logs` - Triggered alert history

#### Configuration

Environment variables in `.env`:
```env
MONITOR_POLLING_INTERVAL=60
MONITOR_RETENTION_DAYS=7
SYNOLOGY_API_VERSION=7
PROMETHEUS_ENABLED=true
PROMETHEUS_TOKEN=optional-token
ALERT_COOLDOWN_DEFAULT=15
```

## Development Commands

```bash
# Install dependencies
composer install
npm install

# Run migrations
php artisan migrate

# Seed monitor data
php artisan db:seed --class=MonitorSeeder

# Development server
npm run dev

# Production build
npm run build

# Run scheduler (for metric collection)
php artisan schedule:work
```

## Key Patterns

### Controllers
- Use Inertia::render() for page responses
- Form requests for validation
- Flash messages via session for mutations

### Vue Components
- Composition API with `<script setup>`
- Import from `@inertiajs/vue3` for Link, Head, useForm, router
- AuthenticatedLayout wrapper for authenticated pages
- Tailwind CSS for styling

### Services
- Collectors implement `CollectorInterface`
- MetricService for metric queries and aggregation
- AlertService for alert evaluation and notifications

## Testing

```bash
# Run tests
php artisan test

# Test specific collector
php artisan tinker
>>> app(App\Services\Collectors\SynologyCollector::class)->testConnection($node)
```

## Health Endpoint Spec

Laravel apps being monitored should implement `/health` endpoint:
```json
{
  "status": "healthy|degraded|unhealthy",
  "checks": {
    "database": true,
    "cache": true,
    "queue": {"size": 5, "failed": 0}
  },
  "response_time_ms": 45
}
```

See `docs/health-endpoint-spec.md` for full specification.
