<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlertLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'alert_id',
        'node_id',
        'metric_value',
        'message',
        'created_at',
    ];

    protected $casts = [
        'metric_value' => 'decimal:4',
        'created_at' => 'datetime',
    ];

    public function alert(): BelongsTo
    {
        return $this->belongsTo(Alert::class);
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    public static function booted(): void
    {
        static::creating(function (AlertLog $log) {
            $log->created_at = $log->created_at ?? now();
        });
    }
}
