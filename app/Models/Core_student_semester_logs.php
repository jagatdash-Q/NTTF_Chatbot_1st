<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;

class Core_student_semester_logs extends Model
{
    use HasFactory, LogsActivity;
    protected $table = 'core_student_semester_logs';
    protected $fillable = [
        'student_lead_id',
        'student_id',
        'zone_id',
        'center_id',
        'program_id',
        'current_semester',
        'semester_no',
        'assigned_by',
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
