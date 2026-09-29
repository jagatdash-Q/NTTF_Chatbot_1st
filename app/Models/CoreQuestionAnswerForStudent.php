<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CoreQuestionAnswerForStudent extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = [
        'core_student_id',
        'core_token_id',
        'core_student_name',
        'exam_type',
        'suppl_no',
        'core_question_ans_id',
        'is_ans_uploaded',
        'semester_id',
        'subject_id',
        'program_id',
        'stream_id',
        'academic_year_span',
        'center_id'
    ];

    public $table = 'core_question_answer_for_students';
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    // In CoreQuestionAnswerForStudent.php
// public function questionAnswer()
// {
//     return $this->belongsTo(CoreQuestionAnswer::class, 'core_question_ans_id');
// }

// public function student()
// {
//     return $this->belongsTo(StudentAdmission::class, 'core_student_id');
// }

}
