<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LegsQuiz extends Model
{
    use HasFactory, LogsActivity;

    protected $primaryKey = 'quiz_id';

    protected $fillable = ['question', 'set_type', 'opt1', 'opt2', 'opt3', 'opt4', 'ans', 'status', 'question_image', 'created_by', 'updated_by', 'quiz_subject_name'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();

    }

    public function quizType()
    {
        return $this->belongsTo(LegsQuizType::class, 'quiz_subject_name');
    }

}
