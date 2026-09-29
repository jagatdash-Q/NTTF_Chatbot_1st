<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoreSemesterDuration extends Model
{
    protected $table = 'core_semester_duration';

    protected $fillable = [
        'program_id',
        'program_type',
        'stream_id',
        'stream_name',
        'academic_year_id',
        'academic_year_month',
        'semester_id',
        'semester_name',
        'start_date',
        'end_date',
        'created_by',
        'updated_by',
    ];

   
}