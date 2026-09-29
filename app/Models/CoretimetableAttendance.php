<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;

class CoretimetableAttendance extends Model
{
    use HasFactory;

protected $fillable = [
    'student_details',

    'academic_time_table_id',
    'program_id',
    'program_name',
    'center_id',
    'center_name',
    'stream_id',
    'stream_name',
    'batch_id',
    'batch_name',
    'semester_no',
    'section_id',
    'section_name',
    'core_year',

    'subject_id',
    'subject_name',

    'session_start_time',
    'session_end_time',

    'totalhour',
    'remainhour',
    'hour_per_semester',

    'employee_id',
    'employee_name',

    'assigned_by_id',
    'assigned_by_name',

    'created_by',
    'attendance_date',
    'topic_details','group_id', 'group_name'
];



    public $table = 'core_timetable_attendance';
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

}
