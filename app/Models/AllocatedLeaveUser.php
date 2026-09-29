<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;

class AllocatedLeaveUser extends Model
{
    use HasFactory, LogsActivity;

   
    protected $table = 'allocated_leaves_users';


    protected $fillable = [
        'user_id',
        'leave_type_id',
        'leave_types',  
        'allocated_days',
        'created_by', 
        'updated_by', 
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }


}
