<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoreStudentOnduty extends Model
{
    protected $table = 'core_student_onduty';

    protected $fillable = [
        'student_lead_id',
        'student_token',
        'from_date',
        'to_date',
        'reason',
        'created_by',
        'updated_by',
    ];
}