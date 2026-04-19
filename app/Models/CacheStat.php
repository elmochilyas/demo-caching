<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CacheStat extends Model
{
    protected $fillable = [
        'endpoint',
        'cache_status',
        'response_time_ms',
        'user_session_id',
    ];
}