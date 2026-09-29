<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoreStudentStatusChangeLog extends Model
{
    protected $fillable = [
        'student_admission_id',
        'student_lead_id',
        'student_code',
        'old_status',
        'new_status',
        'changed_by_user_id',
        'changed_date',
        'remark',
    ];
}
