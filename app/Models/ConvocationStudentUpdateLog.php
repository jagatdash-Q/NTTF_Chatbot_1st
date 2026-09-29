<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConvocationStudentUpdateLog extends Model
{
    protected $fillable = [
        'student_admission_id',
        'student_lead_id',
        'confirmation_id',
        'field_name',
        'old_value',
        'new_value',
        'updated_by',
    ];
}