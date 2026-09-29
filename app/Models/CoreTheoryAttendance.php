<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
// huse Spatie\Activitylog\Traits\LogsActivity;

class CoreTheoryAttendance extends Model
{
    use HasFactory, LogsActivity;
    // use HasFactory, LogOptions,LogsActivity;

    protected $fillable = [
        'batch_id',
        'batch_name',
        'subject_id',
        'subject_name',
        'program_id',
        'program_name',
        'date',
        'day',
        'time',
        'core_time_table_id',
        'student_id',
        'is_present',
        'updated_by',
        'created_by',
    ];


    public $table = 'core_theory_attendances';
    public function getActivitylogOptions(): LogOptions
    {
        // Customize which attributes to log
        return LogOptions::defaults()
            ->logOnly([
                'batch_id',
                'subject_id',
                'date',
                'student_id',
                'is_present',
                'updated_by',
            ])
            ->useLogName('core_theory_attendance');
    }
}
