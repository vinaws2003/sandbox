<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Metric extends Model
{
    use HasFactory;

    protected $fillable = [
        'node_id',
        'type',
        'value',
        'metadata',
        'recorded_at',
    ];

    protected $casts = [
        'value' => 'decimal:4',
        'metadata' => 'array',
        'recorded_at' => 'datetime',
    ];

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeRecordedAfter($query, $datetime)
    {
        return $query->where('recorded_at', '>=', $datetime);
    }

    public function scopeRecordedBefore($query, $datetime)
    {
        return $query->where('recorded_at', '<=', $datetime);
    }

    public function scopeForNode($query, int $nodeId)
    {
        return $query->where('node_id', $nodeId);
    }
}
