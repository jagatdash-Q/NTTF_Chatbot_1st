<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoreStudentAdditionalInfo extends Model
{
    protected $fillable = [
        'student_lead_id',
        'student_id',
        'placement_status',
        'mentor_tag',
        'assigned_by',
    ];
}
