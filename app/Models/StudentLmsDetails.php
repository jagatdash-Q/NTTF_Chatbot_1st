<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentLmsDetails extends Model
{
    protected $fillable = [
        'student_id','lms_user_id'
    ];
}
