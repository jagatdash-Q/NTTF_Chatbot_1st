<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoreStudentTransferLogs extends Model
{
    protected $fillable = [
        'student_lead_id',
        'old_student_code',
        'old_student_status',
        'old_center',
        'old_stream',
        'old_academic_year',
        'old_batch',
        'old_semester',
        'new_student_code',
        'new_center',
        'new_stream',
        'new_academic_year',
        'new_batch',
        'new_semester',
        'new_student_status',
        'change_by',
        'transfer_date',
        'remarks',
    ];
}
