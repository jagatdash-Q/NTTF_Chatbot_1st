<?php

namespace App\Models\AcademicSectionModels;

use App\Models\QuizModels\Quiz;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizType extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'status', 'alies_name'];


    public function quizzes()
    {
        return $this->hasMany(Quiz::class, 'quiz_subject_name');
    }
}
