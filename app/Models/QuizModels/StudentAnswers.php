<?php

namespace App\Models\QuizModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class StudentAnswers extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;
    public $table = "student_answers";
    protected $fillable = [
        'student_id',
        'exam_id',
        'question_id',
        'selected_option',
        'is_correct',
        'status'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
