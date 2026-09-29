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

class OndutyStatus extends Model
{
    use HasFactory,  LogsActivity;
    protected $fillable = [
        'userid',
        'employee_nttf_id',
        'checkin_type',
        'date',
        'time',
        'approval_status',
        'name',
        'email',
        'approvedby_id',
        'approvedby_name',
        'attendance_id'
    ];
    public $table = 'onduty_status';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
    
}
