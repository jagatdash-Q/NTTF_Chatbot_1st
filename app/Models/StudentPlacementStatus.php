<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;

class StudentPlacementStatus extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'student_placement_status';

    protected $fillable = [
        'event_id',
        'student_id',
        'program_id',
        'placement_status',
        'course_name',
        'institution_name',
        'company_name',
        'designation',
        'salary',
        'job_location',
        'remarks',
        'created_by',
        'updated_by',
    ];
  public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }  
}
