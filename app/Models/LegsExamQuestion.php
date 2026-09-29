<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegsExamQuestion extends Model
{
    protected $fillable = [
        'exam_id',
        'question_id',
    ];

    public function exam()
    {
        return $this->belongsTo(LegsExam::class);
    }

    public function question()
    {
        return $this->belongsTo(LegsQuiz::class);
    }
}
