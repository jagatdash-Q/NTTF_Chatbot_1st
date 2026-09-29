<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoreStudentAdmissionStatusChangeLog extends Model
{
    protected $fillable = [
        'student_lead_id',
        'old_status',
        'new_status',
        'gap_old_new_status',
        'status_changed_by',
    ];
}
