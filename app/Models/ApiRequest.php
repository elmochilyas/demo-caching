<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApiRequest extends Model
{
    protected $fillable = [
        'user_session_id',
        'endpoint',
        'request_count',
        'api_call_count',
        'response_time_ms',
    ];
}