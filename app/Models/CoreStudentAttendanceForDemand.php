<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoreStudentAttendanceForDemand extends Model
{
    protected $fillable = [
        'student_lead_id',
        'attendance_mark',
        'attendance_marked_by',
        'updated_by',
    ];
}
