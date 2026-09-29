<?php

namespace App\Models\QuizModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ExamResults extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;
    protected $fillable = [
        'student_id',
        'exam_id',
        'total_question',
        'answered_question',
        'obtained_mark',
        'skip_count',
        'result_date',
        'correct_ans_count',
        'incorrect_ans_count',
        'percentage',
        'is_rescheduled'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
