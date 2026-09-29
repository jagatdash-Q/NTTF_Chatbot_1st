<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrincipalReportBlocker extends Model
{
    protected $table = 'principal_report_blockers';

    protected $fillable = [
        'center_id',
        'month_year',
        'blocker_text',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}