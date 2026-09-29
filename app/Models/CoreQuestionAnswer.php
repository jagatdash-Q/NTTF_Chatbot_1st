<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\CoreProgrammeModels\StudentAdmission;

class CoreQuestionAnswer extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = [
        'document_name',
        'document_path',
        'evaluator_mem_id',
        'status',
        'evaluation_date',
        'subject_id',
        'program_id',
        'stream_id',
        'center_id',
        'semester_id',
        'core_zone_id',
        'user_id',
        'batch_id',
        'document_types',
        'created_by',
        'updated_by',
        'exam_types',
        'program_name',
        'stream_name',
        'subject_name',
        'semester_name',
        'center_name',
        'evaluator_name',
        'batch_name',
        'user_name',
        'core_zone_name',
        'student_id',
        'student_token_id',
        'student_name',
        'start_time',
        'end_time',

        'assign_start_date_time',
        'assign_end_date_time',
        'exam_type',
        'suppl_no',
        'core_student_id',
        'is_answer_readable',
        'is_marked_or_not',
        'academic_year',
        'academic_month',
        'academic_year_span',
        'question_answer_key',
        'question_answer_key_file_name',
        'evaluator_1_id',
        'evaluator_2_id',
        'evaluator_3_id',
        'marks_1',
        'marks_2',
        'marks_3',
        'total_marks',
        'final_marks',
        'is_first_evaluated',
        'is_second_evaluated',
        'is_third_evaluated',
        'is_final_evaluated',
        'request_for_reevaluate',
        'assigned_evaluator',
        'reevaluate_assigned_evaluator',
        'reevaluate_status',
        'is_assigned_or_not',
       
        



    ];
    public $table = 'core_question_answers';
    public function studentAdmission()
    {
        return $this->belongsTo(
            StudentAdmission::class,
            'student_token_id',
            'core_student_code'
        );
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
