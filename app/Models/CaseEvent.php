<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Movimiento extends Model
{
    use HasUuids;

    protected $table = 'case_events';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'case_id',
        'event_date',
        'event_type',
        'description',
        'is_payment',
    ];

    protected $casts = [
        'event_date' => 'date',
        'is_payment' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(Expediente::class, 'case_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
