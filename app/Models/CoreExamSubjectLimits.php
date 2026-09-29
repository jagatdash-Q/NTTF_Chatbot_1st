<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use App\Models\CoreProgrammeModels\StudentAdmission;

class CoreExamSubjectLimits extends Model
{
    use HasFactory;

   protected $fillable = [
        'subject_code',
        'subject_id',
        'subject_name',
        'academic_year',
        'exam_type',
        'suppl_no',

        's_min',
        's_max',
        'e_min',
        'e_max',
        't_min',
        't_max',
        'gr_min',
        'gr_max',
        'gp_min',
        'gp_max',
    ];

    
    public $table = 'core_exam_subject_limits';
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
   


    
}
