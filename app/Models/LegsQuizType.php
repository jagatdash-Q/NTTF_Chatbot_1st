<?php

namespace App\Models;

use App\Models\LegsQuiz;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegsQuizType extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'status', 'alies_name'];

    public function quizzes()
    {
        return $this->hasMany(LegsQuiz::class, 'quiz_subject_name');
    }
}
