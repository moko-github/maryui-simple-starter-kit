<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessRequest extends Model
{
    protected $fillable = [
        'user_id',
        'kerberos',
        'justification',
        'status',
        'processed_by',
        'processed_at',
        'admin_message',
    ];

    protected function casts(): array
    {
        return [
            'processed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function scopePending($query): mixed
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved($query): mixed
    {
        return $query->where('status', 'approved');
    }

    public function scopeRejected($query): mixed
    {
        return $query->where('status', 'rejected');
    }
}
