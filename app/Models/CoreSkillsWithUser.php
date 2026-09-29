<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Storage;

class CoreSkillsWithUser extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = [
        'program_id',
        'stream_id',
        'center_id',
        'core_zone_id',
        'subject_id',
        'user_id',
        'user_name',
        'subject_name',
        'program_name',
        'stream_name',
        'core_zone_name',
        'center_name',
        'level',
        'academic_year',
        'academic_month',
        'academic_year_span',
        'semester_id',
        'semester_name',
        'created_by',
        'updated_by','start_date', 'end_date'
        
    ];
    public $table = 'core_skills_with_users';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
    
}
