<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveredFact extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'package_id', 
        'fact_id',
        'scheduled_at',
        'delivered_at',
        'seen_at',
        'status',
        'channel',
        'attempts',
        'meta',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'delivered_at' => 'datetime', 
        'seen_at' => 'datetime',
        'meta' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function fact(): BelongsTo
    {
        return $this->belongsTo(Fact::class);
    }
}
