<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;

class EmployeeLeaveRequest extends Model
{
    use HasFactory, LogsActivity;

    // Table name
    protected $table = 'employee_leave_request';

    // Fillable fields
    protected $fillable = [
        'user_id',
        'leave_type_id',
        'leave_type',
        'half_day_leave',
        'from_date',
        'to_date',
        'document',
        'reason',
        'status',
        'created_by',
        'updated_by',
        'total_days',
        'half_day_shift'
    ];

   public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
 

  
}
