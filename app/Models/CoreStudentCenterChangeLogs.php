<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoreStudentCenterChangeLogs extends Model
{

    protected $table = 'core_student_course_and_center_change_logs';
    protected $fillable = [
        'student_lead_id',
        'old_center_id',
        'new_center_id',
        'remark',
        'change_date',
        'changed_by',
        'old_stream_id',
        'new_stream_id',
        'old_program_id',
        'new_program_id',
    ];


}
