<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TheoryAttendance extends Model
{
    protected $fillable = ['batch_id', 'subject_id', 'date', 'time', 'time_table_id', 'student_id', 'is_present','day'];
}
