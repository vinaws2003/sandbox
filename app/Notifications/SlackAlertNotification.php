<?php

namespace App\Notifications;

use App\Models\Alert;
use App\Models\Node;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;

class SlackAlertNotification extends Notification implements ShouldQueue
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
        return ['slack'];
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        $color = $this->getAlertColor();

        return (new SlackMessage)
            ->error()
            ->attachment(function ($attachment) use ($color) {
                $attachment
                    ->title("Monitor Alert: {$this->node->name}")
                    ->content($this->message)
                    ->color($color)
                    ->fields([
                        'Node' => $this->node->name,
                        'Type' => $this->node->type,
                        'Metric' => $this->alert->metric_type,
                        'Value' => number_format($this->value, 2),
                        'Threshold' => "{$this->alert->getConditionLabel()} " . number_format($this->alert->threshold, 2),
                    ])
                    ->timestamp(now());
            });
    }

    protected function getAlertColor(): string
    {
        // Determine color based on how far the value exceeds the threshold
        $percentOver = abs(($this->value - $this->alert->threshold) / $this->alert->threshold) * 100;

        if ($percentOver > 20) {
            return 'danger';
        } elseif ($percentOver > 10) {
            return 'warning';
        }

        return 'warning';
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
