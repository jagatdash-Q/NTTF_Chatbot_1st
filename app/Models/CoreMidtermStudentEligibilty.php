<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;

class CoreMidtermStudentEligibilty extends Model
{
     use HasFactory;
    protected $fillable = [
        'subject_id',
        'program_id',
        'stream_id',
        'center_id',
        'semester_id',
        'core_zone_id',
        'subject_name',
        'program_name',
        'stream_name',
        'center_name',
        'zone_name',
        'core_student_id',
        'token_number',
        'batch_id',
        'batch_name',
        'exam_type',
        'test_type',
        'stream_types',
        'theory_percentage',
        'practical_percentage',
        'attendance_percentage',
        'payment_percentage',
        'eligible_status',
        'mail_status',
        'remarks',
        'academic_year_span',
        'core_student_name'
    ];
    public $table = 'core_midterm_student_eligibilties';
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
