<?php

namespace App\Http\Controllers\Monitor;

use App\Http\Controllers\Controller;
use App\Models\AlertLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AlertHistoryController extends Controller
{
    public function index(Request $request): Response
    {
        $query = AlertLog::query()
            ->with(['alert', 'node'])
            ->orderByDesc('created_at');

        if ($request->has('node_id')) {
            $query->where('node_id', $request->get('node_id'));
        }

        if ($request->has('alert_id')) {
            $query->where('alert_id', $request->get('alert_id'));
        }

        $logs = $query->paginate(25)->through(fn (AlertLog $log) => [
            'id' => $log->id,
            'alert_id' => $log->alert_id,
            'node_id' => $log->node_id,
            'node_name' => $log->node->name,
            'node_type' => $log->node->type,
            'metric_type' => $log->alert->metric_type,
            'condition' => $log->alert->condition,
            'threshold' => $log->alert->threshold,
            'value' => $log->metric_value,
            'message' => $log->message,
            'created_at' => $log->created_at->format('Y-m-d H:i:s'),
            'created_at_human' => $log->created_at->diffForHumans(),
        ]);

        return Inertia::render('Monitor/Alerts/History', [
            'logs' => $logs,
            'filters' => [
                'node_id' => $request->get('node_id'),
                'alert_id' => $request->get('alert_id'),
            ],
        ]);
    }
}
