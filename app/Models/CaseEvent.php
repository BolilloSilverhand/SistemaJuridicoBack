<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseEvent extends Model
{
    protected $table = 'case_events';

    protected $fillable = [
        'user_id',
        'legal_case_id',
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

    public function legalCase(): BelongsTo
    {
        return $this->belongsTo(LegalCase::class, 'legal_case_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
