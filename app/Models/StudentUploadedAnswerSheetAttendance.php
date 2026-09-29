<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;

class StudentUploadedAnswerSheetAttendance extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'student_uploaded_answer_sheet_attendances';

   protected $fillable = [
    'core_student_id',
    'core_student_code',
    'attendance_status',
    'exam_type',
    'academic_year_span',
    'center_id',
    'program_id',
    'stream_id',
    'semester_id',
    'updated_by',
    'created_by'
];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
