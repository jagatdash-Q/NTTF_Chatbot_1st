<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;

class CoreTimeTable extends Model
{
    use HasFactory;

    protected $fillable = [
        'core_zone_id',
        'program_id',
        'stream_id', 
        'center_id',
        'semester_id',
        'batch_id',
        'subject_id',
        'subject_name',
        'program_name',
        'stream_name',
        'center_name',
        'zone_name',
        'emp_id',
        'location',
        'other_location',
        'staff',
        'day_of_week',
        'start_time',
        'end_time',
        'start_date',
        'end_date',
        'recurrence_type',

                
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'start_date' => 'date',
        'end_date' => 'date',
    ];
     public $table = 'core_time_tables';
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

     public function subject()
    {
        return $this->belongsTo(\App\Models\AcademicSectionModels\Subjects::class, 'subject_id');
    }

    // If emp_id references employees table (Employee model)
    public function emp()
    {
        return $this->belongsTo(\App\Models\Employee::class, 'emp_id');
    }

    public function semester()
    {
        return $this->belongsTo(\App\Models\AcademicSectionModels\Semesters::class, 'semester_id');
    }
    // public function batch(): BelongsTo
    // {
    //     return $this->belongsTo(LegsStudentBatch::class);
    // }


    // public function semester()
    // {
    //     return $this->belongsTo(LegsSemesters::class);
    // }
}
