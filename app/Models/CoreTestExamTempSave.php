<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoreTestExamTempSave extends Model
{
    protected $fillable = [
        'core_test_exam_temp_results_id',
        'student_id',
        'exam_id',
        'question_id',
        'selected_option',
    ];
}
