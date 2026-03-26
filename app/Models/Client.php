<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    protected $table = 'clients';

    protected $fillable = [
        'user_id',
        'name',
        'last_name',
        'agreed_amount',
        'total_debt',
        'paid',
    ];

    protected $casts = [
        'agreed_amount' => 'decimal:2',
        'total_debt' => 'decimal:2',
        'paid' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cases(): HasMany
    {
        return $this->hasMany(LegalCase::class, 'client_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(ClientTransaction::class, 'client_id');
    }
}
