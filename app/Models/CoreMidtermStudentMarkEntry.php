<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;

class CoreMidtermStudentMarkEntry extends Model
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
        'status',
        'intermediate_marks',
        'file_marks',
        'marks',
        'design_mark',
        'academic_year_span',
        'employebillity_skill_marks',
        'grace_marks',
        'total_marks',
        'theory_file_mark',
        'practical_file_mark',
        'midterm_mark_type',
        'core_student_name',
        'is_freeze',
        'midterm_evaluation_type'
    ];
    public $table = 'core_midterm_student_mark_entries';
     protected $casts = [
        'status' => 'array', 
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
