<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegsStudentSubjectSemesterResult extends Model
{
    protected $table = 'legs_student_subject_semester_result';

    protected $fillable = [
        'uuid',
        'student_code',
        'batch_id',
        'legs_semesters_id',
        'exam_type',

        'gr_total',
        'result',
        'result_code',

        'sgpa',
        'sgpa_class',

        'cgpa',
        'cgpa_class',

        'con_sgpa',
        'con_sgpa_class',

        'con_cgpa',
        'con_cgpa_class',

        'month_of_passing',
        'year_of_passing',
    ];
    protected $casts = [
        'sgpa' => 'float',
        'cgpa' => 'float',
        'con_sgpa' => 'float',
        'con_cgpa' => 'float',
    ];
}