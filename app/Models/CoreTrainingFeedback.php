<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use App\Models\CoreProgrammeModels\StudentAdmission;

class CoreTrainingFeedback extends Model
{
    use HasFactory;

  protected $fillable = [
        'user_id','program_id','stream_id','center_id','subject_id','semester_id',
        'user_name','department','program_name','subject_name','center_name','duration',
        'content_relevance','knowledge_increase','applicability','career_support',
        'overall_rating','expected_improvement_percentage','suggestions',
        'pre_test_marks','post_test_marks','submitted_date'
    ];

    public $table = 'core_training_feedback';
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
    


    
}
