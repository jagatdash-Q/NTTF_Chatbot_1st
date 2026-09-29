<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;

class CoreHiringRequirement extends Model
{
    use HasFactory, LogsActivity;

    // Specify table name explicitly
    protected $table = 'core_hiringrequirement';

    // Mass assignable fields
  protected $fillable = [
    
    'program_id',
    'stream_id',
    'requirement_for',
    'company_id',
    'company_name',
    'position_name',
    'no_of_vacancies',
    'job_description',
    'probable_ctc',
    'probable_doj',
    'job_location',
    'selection_criteria',
    'required_date',
    'interview_at',
    'gender',
    'internship_duration',
    'jd_file',
    'selection_process',
    'tenth_marks',
    'twelfth_marks',
    'diploma_marks',
    'academic_gap',
    'placement_year',
    'placement_year_id',
    'created_by',
    'updated_by',
];
     public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
