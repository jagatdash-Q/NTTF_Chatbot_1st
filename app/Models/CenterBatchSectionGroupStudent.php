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

class CenterBatchSectionGroupStudent extends Model
{
    use HasFactory,  LogsActivity;
    protected $fillable = [
        'center_id',
        'batch_id',
        'section_id',
        'group_id',
        'core_student_id',
    ];
    public $table = 'center_batch_section_group_students';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
    
}
