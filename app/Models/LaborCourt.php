<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LaborCourt extends Model
{
    protected $table = 'labor_courts';

    protected $fillable = [
        'user_id',
        'name',
        'address',
        'phone',
        'email',
        'has_non_conciliation_certificate',
    ];

    protected $casts = [
        'has_non_conciliation_certificate' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
