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

class CoreGroups extends Model
{
    use HasFactory,  LogsActivity;
    protected $fillable = [
        'center_id',
        'group_name',
        'group_code',
        'group_start_date',
        'group_end_date',
        'academic_year',
        'academic_month',
        'created_by',
        'updated_by',
        'is_active',
        'group_type',
        'batch_id',
        'section_id',
        'core_zone_id',              
        'program_id',
        'academic_year_id',
        'stream_id',
        'group_students',
        'year_month'

        
    ];
    public $table = 'core_groups';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
    
}
