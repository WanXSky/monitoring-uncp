<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Website extends Model
{
    protected $table = 'websites';

    protected $fillable = [
        'name', 'url', 'status', 'response_time',
        'ssl_expired_at', 'last_checked',
    ];

    protected $casts = [
        'status' => 'boolean',
        'ssl_expired_at' => 'date',
        'last_checked' => 'datetime',
    ];

    public function logs(): HasMany
    {
        return $this->hasMany(MonitoringLog::class, 'website_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'website_id');
    }
}
