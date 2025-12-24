<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringLog extends Model
{
    protected $table = 'monitoring_logs';
    public $timestamps = false;

    protected $fillable = ['website_id', 'status', 'response_time', 'checked_at'];

    protected $casts = [
        'status' => 'boolean',
        'checked_at' => 'datetime',
    ];

    public function website(): BelongsTo
    {
        return $this->belongsTo(Website::class, 'website_id');
    }
}
