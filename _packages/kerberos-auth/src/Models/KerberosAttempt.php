<?php

namespace MokoGithub\KerberosAuth\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class KerberosAttempt extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'kerberos',
        'result',
        'ip_address',
        'user_agent',
        'attempted_at',
    ];

    protected function casts(): array
    {
        return [
            'attempted_at' => 'datetime',
        ];
    }

    public function scopePurgeOld($query, int $days = 30): mixed
    {
        return $query->where('attempted_at', '<', Carbon::now()->subDays($days));
    }
}
