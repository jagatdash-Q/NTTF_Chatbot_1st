<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
class LeaveLog extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'leave_logs';

    protected $fillable = [
        'user_id', 
        'status', 
        
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
    
}
