<?php

namespace App\Models\QuizModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizQuestionExam extends Model
{
    use HasFactory;

    protected $fillable = ['exam_id','quiz_question_id'];
}
