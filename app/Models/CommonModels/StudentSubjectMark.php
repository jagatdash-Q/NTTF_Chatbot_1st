<?php

namespace App\Models\CommonModels;

use Illuminate\Database\Eloquent\Model;

class StudentSubjectMark extends Model
{
    public $fillable = [
        'student_enrollment_id',
        'subject_name',
        'full_mark',
        'obtain_mark',
        'percentage',
        'student_lead_id'
    ];
}
