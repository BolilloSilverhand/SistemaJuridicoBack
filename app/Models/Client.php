<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    use HasUuids;

    protected $table = 'clients';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'user_id',
        'name',
        'last_name',
        'agreed_amount',
        'total_debt',
    ];

    protected $casts = [
        'agreed_amount' => 'decimal:2',
        'total_debt' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cases(): HasMany
    {
        return $this->hasMany(Expediente::class, 'client_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(ClienteMovimiento::class, 'client_id');
    }
}
