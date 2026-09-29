<?php

namespace App\Models\LegsProgrammeModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class LegsCollegeAcademicProgrammeStreams extends Model
{
    use HasFactory ,SoftDeletes,LogsActivity;
    protected $fillable = ['academic_programme_id','stream_name','stream_code','status','program_title','prog_year','program_years'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
       
    }
    protected $casts = [
        'program_years' => 'array',
    ];
}
