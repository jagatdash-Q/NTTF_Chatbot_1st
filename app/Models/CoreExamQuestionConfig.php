<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\UuidV7;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CoreExamQuestionConfig extends Model
{
    use HasFactory;

    protected $table = 'core_exam_question_configs';

    protected $fillable = [
        'uuid',
        "program_name",
        "program_id",
        "branch_name",
        "branch_id",
        "semester",
        'batch_month', 
        'batch_year',  
        'subject_code',
        'subject_name',
       
        'created_by',
        'updated_by',
        'core_batch_id',
        'core_batch_name',

        'exam_name',
        'suppl_no',
        'part',
        'no_of_questions',
        'max_to_answer',
        'status' ,
        'core_zone_id',
        'core_zone_name',
        'center_id',
        'center_name',
        'stream_name',
        'stream_id',
       
        'academic_year_span',
        'mark_per_question'   
    ];

    /**
     *  Setup model event hooks
     */
    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->uuid = new UuidV7();
        });
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}