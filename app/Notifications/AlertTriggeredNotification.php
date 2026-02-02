<?php

namespace App\Notifications;

use App\Models\Alert;
use App\Models\Node;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AlertTriggeredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Alert $alert,
        public Node $node,
        public float $value,
        public string $message
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('monitor.nodes.show', $this->node);

        return (new MailMessage)
            ->subject("Monitor Alert: {$this->alert->metric_type} on {$this->node->name}")
            ->greeting('Monitor Alert Triggered')
            ->line($this->message)
            ->line("**Node:** {$this->node->name}")
            ->line("**Type:** {$this->node->type}")
            ->line("**Metric:** {$this->alert->metric_type}")
            ->line("**Value:** " . number_format($this->value, 2))
            ->line("**Threshold:** {$this->alert->getConditionLabel()} " . number_format($this->alert->threshold, 2))
            ->action('View Node Details', $url)
            ->line('This alert will not fire again for ' . $this->alert->cooldown_minutes . ' minutes.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'alert_id' => $this->alert->id,
            'node_id' => $this->node->id,
            'node_name' => $this->node->name,
            'metric_type' => $this->alert->metric_type,
            'value' => $this->value,
            'threshold' => $this->alert->threshold,
            'condition' => $this->alert->condition,
            'message' => $this->message,
        ];
    }
}
