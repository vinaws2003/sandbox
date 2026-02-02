<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'node_id',
        'metric_type',
        'condition',
        'threshold',
        'notification_channel',
        'notification_target',
        'is_active',
        'cooldown_minutes',
        'last_triggered_at',
    ];

    protected $casts = [
        'threshold' => 'decimal:4',
        'is_active' => 'boolean',
        'cooldown_minutes' => 'integer',
        'last_triggered_at' => 'datetime',
    ];

    public const CONDITIONS = [
        'gt' => 'Greater than',
        'gte' => 'Greater than or equal',
        'lt' => 'Less than',
        'lte' => 'Less than or equal',
        'eq' => 'Equal to',
        'neq' => 'Not equal to',
    ];

    public const CHANNELS = [
        'mail' => 'Email',
        'slack' => 'Slack',
        'database' => 'Database',
    ];

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    public function alertLogs(): HasMany
    {
        return $this->hasMany(AlertLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function isOnCooldown(): bool
    {
        if (!$this->last_triggered_at) {
            return false;
        }

        return $this->last_triggered_at->addMinutes($this->cooldown_minutes)->isFuture();
    }

    public function evaluate(float $value): bool
    {
        return match ($this->condition) {
            'gt' => $value > $this->threshold,
            'gte' => $value >= $this->threshold,
            'lt' => $value < $this->threshold,
            'lte' => $value <= $this->threshold,
            'eq' => $value == $this->threshold,
            'neq' => $value != $this->threshold,
            default => false,
        };
    }

    public function getConditionLabel(): string
    {
        return self::CONDITIONS[$this->condition] ?? $this->condition;
    }

    public function getChannelLabel(): string
    {
        return self::CHANNELS[$this->notification_channel] ?? $this->notification_channel;
    }
}
