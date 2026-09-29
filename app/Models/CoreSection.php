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

class CoreSection extends Model
{
    use HasFactory,  LogsActivity;
    protected $fillable = [
        'center_id',
        'batch_id',
        'section_name',
        'section_code',
        'section_start_date',
        'section_end_date',
        'academic_year',
        'academic_month',
        'created_by',
        'updated_by',
        'core_zone_id',
        'section_students',
        'program_id',
        'stream_id',
        'academic_year_id',
        'section_incharge',
        'year_month'
    ];
    public $table = 'core_sections';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
    
}
