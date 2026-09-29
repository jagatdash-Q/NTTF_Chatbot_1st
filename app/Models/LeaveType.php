<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
class LeaveType extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'leave_types';

    protected $fillable = [
        'leave_type', 
        'total_days', 
        'created_by', 
        'updated_by', 
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
    
}
