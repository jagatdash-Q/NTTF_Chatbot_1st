<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\CoreExamCellQuestionUpload;
use App\Models\CoreQuestionAnswer;
use App\Models\CoreStudentsDigitalMark;

class CoreQuestionAnswerDiscrepancies extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = [
        'core_question_upload_id',
        'reported_by',
        'reported_by_name',
        'evaluator_id',
        'evaluator_name',
        'reported_to',
        'reported_to_name',
        'core_question_answer_id',
        'core_student_digital_mark_id',
        'core_question_answer_document',
        'core_question_document',
        'remarks',
        'discrepancy_date',
        'status',
    ];
   
    public $table = 'core_question_answer_discrepancies';

    public function questionAnswer()
    {
        return $this->belongsTo(
            CoreQuestionAnswer::class,
            'core_question_answer_id'
        );
    }

    public function digitalMark()
    {
        return $this->belongsTo(
            CoreStudentsDigitalMark::class,
            'core_student_digital_mark_id'
        );
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
