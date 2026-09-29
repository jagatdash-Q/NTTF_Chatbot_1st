<?php

namespace App\Models\CommonModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class QuotaSystem extends Model
{
    use HasFactory, Notifiable, SoftDeletes, LogsActivity;
    protected $table = 'quota_system';
    protected $fillable = [
        'uuid',
        'quota_name',
        'quota_description',
        'quota_percentage',
        'type',
        'is_active',
        'remarks',
        'status',
        'created_by',
        'updated_by',
        'lateral_entery','certificate_course_duration'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
