<?php

namespace App\Http\Controllers\Monitor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Monitor\StoreAlertRequest;
use App\Http\Requests\Monitor\UpdateAlertRequest;
use App\Models\Alert;
use App\Models\Node;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class AlertController extends Controller
{
    public function index(): Response
    {
        $alerts = Alert::query()
            ->with('node')
            ->withCount('alertLogs')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Alert $alert) => [
                'id' => $alert->id,
                'node_id' => $alert->node_id,
                'node_name' => $alert->node?->name ?? 'All Nodes',
                'metric_type' => $alert->metric_type,
                'condition' => $alert->condition,
                'condition_label' => $alert->getConditionLabel(),
                'threshold' => $alert->threshold,
                'notification_channel' => $alert->notification_channel,
                'channel_label' => $alert->getChannelLabel(),
                'notification_target' => $alert->notification_target,
                'is_active' => $alert->is_active,
                'cooldown_minutes' => $alert->cooldown_minutes,
                'last_triggered_at' => $alert->last_triggered_at?->diffForHumans(),
                'alert_logs_count' => $alert->alert_logs_count,
            ]);

        return Inertia::render('Monitor/Alerts/Index', [
            'alerts' => $alerts,
        ]);
    }

    public function create(): Response
    {
        $nodes = Node::query()
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        return Inertia::render('Monitor/Alerts/Create', [
            'nodes' => $nodes,
            'conditions' => Alert::CONDITIONS,
            'channels' => Alert::CHANNELS,
            'metricTypes' => $this->getMetricTypes(),
        ]);
    }

    public function store(StoreAlertRequest $request): RedirectResponse
    {
        $alert = Alert::create($request->validated());

        return redirect()
            ->route('monitor.alerts.index')
            ->with('success', 'Alert rule created successfully.');
    }

    public function edit(Alert $alert): Response
    {
        $nodes = Node::query()
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        return Inertia::render('Monitor/Alerts/Edit', [
            'alert' => [
                'id' => $alert->id,
                'node_id' => $alert->node_id,
                'metric_type' => $alert->metric_type,
                'condition' => $alert->condition,
                'threshold' => $alert->threshold,
                'notification_channel' => $alert->notification_channel,
                'notification_target' => $alert->notification_target,
                'is_active' => $alert->is_active,
                'cooldown_minutes' => $alert->cooldown_minutes,
            ],
            'nodes' => $nodes,
            'conditions' => Alert::CONDITIONS,
            'channels' => Alert::CHANNELS,
            'metricTypes' => $this->getMetricTypes(),
        ]);
    }

    public function update(UpdateAlertRequest $request, Alert $alert): RedirectResponse
    {
        $alert->update($request->validated());

        return redirect()
            ->route('monitor.alerts.index')
            ->with('success', 'Alert rule updated successfully.');
    }

    public function destroy(Alert $alert): RedirectResponse
    {
        $alert->delete();

        return redirect()
            ->route('monitor.alerts.index')
            ->with('success', 'Alert rule deleted successfully.');
    }

    protected function getMetricTypes(): array
    {
        return [
            'General' => [
                'cpu' => 'CPU Usage (%)',
                'memory' => 'Memory Usage (%)',
                'disk' => 'Disk Usage (%)',
                'temperature' => 'Temperature (°C)',
            ],
            'Network' => [
                'network_in' => 'Network In (KB/s)',
                'network_out' => 'Network Out (KB/s)',
            ],
            'Galera' => [
                'wsrep_cluster_size' => 'Cluster Size',
                'wsrep_ready' => 'Ready Status',
                'wsrep_connected' => 'Connected Status',
                'wsrep_flow_control_paused' => 'Flow Control Paused',
            ],
            'Laravel App' => [
                'response_time' => 'Response Time (s)',
                'status' => 'Health Status',
                'queue_size' => 'Queue Size',
                'failed_jobs' => 'Failed Jobs',
            ],
            'Docker' => [
                'container_status' => 'Container Status',
                'container_cpu' => 'Container CPU (%)',
                'container_memory' => 'Container Memory (%)',
                'container_restart_count' => 'Restart Count',
            ],
        ];
    }
}
