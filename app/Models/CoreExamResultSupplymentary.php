<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use App\Models\CoreProgrammeModels\StudentAdmission;

class CoreExamResultSupplymentary extends Model
{
    use HasFactory;

  protected $fillable = [
    'core_exam_result_id',
    'program_id',
    'stream_id',
    'subject_id',
    'student_id',
    'reason',
    'suppl_no',
    'core_student_code',
    'core_academic_year',
    'center_id',
    'semester_id',
    'full_marks',
    'assignment_marks',
    'total_marks',
    'evaluation_type',
    'converted_marks',
    'supply_type',
];


    
    public $table = 'core_exam_result_supplymentaries';
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
    public function student()
{
    return $this->belongsTo(StudentAdmission::class, 'core_student_id');
}


    
}
