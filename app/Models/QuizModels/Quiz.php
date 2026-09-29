<?php

namespace App\Models\QuizModels;

use App\Models\AcademicSectionModels\QuizType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Quiz extends Model
{
    use HasFactory, LogsActivity;

    protected $primaryKey = 'quiz_id';

    protected $fillable = ['question', 'set_type', 'opt1', 'opt2', 'opt3', 'opt4', 'ans', 'status', 'question_image', 'created_by', 'updated_by','quiz_subject_name','test_type','program_type'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();

    }

   

    public function exams()
    {
        return $this->belongsToMany(Exam::class, 'quiz_question_exams', 'quizzes_quiz_id', 'exam_id');
    }

    public function quizType()
    {
        return $this->belongsTo(QuizType::class, 'quiz_subject_name');
    }

   


}
