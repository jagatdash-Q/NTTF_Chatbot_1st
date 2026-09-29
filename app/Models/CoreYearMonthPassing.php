<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoreYearMonthPassing extends Model
{
    protected $table = 'core_year_month_passings';

    protected $fillable = [
          'program_id',
        'stream_id',
        'academic_year_id',
        'core_year',
        'semester_no',
        'exam_type',
        'suppl_no',
        'passing_date',
        'updated_by',
    ];

    protected $casts = [
        'passing_date' => 'date',
        'semester_no' => 'integer',
        'suppl_no' => 'integer',
         'updated_by' => 'integer',
    ];
}