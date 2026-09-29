<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use App\Models\CoreProgrammeModels\StudentAdmission;

class CoreExamResults extends Model
{
    use HasFactory;

   protected $fillable = [
        'core_student_id',
        'core_student_code',
        'core_student_name',
        'center_name',
        'center_id',
        'stream_id',
        'stream_name',
        'program_id',
        'semester_id',
        'subject_code',
        'subject_name',
        'subject_id',
        'midterm_min',
        'midterm_max',
        'endterm_min',
        'endterm_max',
        'total_min',
        'total_max',
        'midterm_marks',
        'endterm_marks',
        'total_marks',
        'sessional',
        'endterm',
        'total',
        'grade',
        'grade_point',
        'grand_total',
        'result_status',
        'sgpa',
        'cgpa',
        'academic_year',
        'academic_year_id',
        'is_publish_to_student',
        'is_publish_to_staff',
        'final_exam_type',
        'suppl_no',
    ];

    
    public $table = 'core_exam_results';
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
    public function student()
{
    return $this->belongsTo(StudentAdmission::class, 'core_student_id');
}


    
}
