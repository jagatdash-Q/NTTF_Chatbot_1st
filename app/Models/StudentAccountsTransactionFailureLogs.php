<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentAccountsTransactionFailureLogs extends Model
{
    protected $fillable = [
        'error_uuid',
        'status_code',
        'method',
        'url',
        'route_name',
        'exception_class',
        'message',
        'file',
        'line',
        'trace',
        'request_data',
        'query_params',
        'headers',
        'user_id',
        'ip_address',
        'user_agent',
        'session_id',
        'environment',
        'app_url',
        'server_ip',
        'referer',
        'is_resolved',
        'resolved_at',
        'resolved_by',
        'context',
    ];

    protected $casts = [
        'request_data' => 'array',
        'query_params' => 'array',
        'headers' => 'array',
        'context' => 'array',
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];
}
