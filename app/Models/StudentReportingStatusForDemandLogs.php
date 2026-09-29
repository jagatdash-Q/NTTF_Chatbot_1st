<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentReportingStatusForDemandLogs extends Model
{
    protected $fillable = [
        'student_lead_id',
        'student_code',
        'created_by',
        'reporting_status'
    ];
}
