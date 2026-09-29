<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoreStudentPasswordResets extends Model
{
    protected $fillable = [
        'student_lead_id',
        'student_code',
        'token',
        'is_validated',
    ];
}
