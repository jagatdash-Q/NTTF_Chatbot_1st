<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DurationWiseCourse extends Model
{
    use HasFactory;

    protected $table = 'duration_wise_course';

    protected $fillable = [
        'program_id',
        'program_name',
        'stream_id',
        'stream_name',
        'certificate_course_duration',
      
    ];

  
}