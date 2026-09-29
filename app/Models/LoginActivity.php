<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\UuidV7;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class LoginActivity extends Model
{
    use HasFactory;

    protected $table = 'login_activities';

    protected $fillable = [
       'user_id',
       'name',
       'email',
       'ip_address',
       'full_address',
       'latitude',
       'longitude',
       'full_address',
       'ip_address',
       'login_time',
       'checkin_time',
       'checkout_time',
       'login_type',
       'logout_type',
       'logoff_type',
       'logout_address',
       'logout_longitude', 
       'logout_latitude',
       'work_hours',
       'login_type',
       'logout_type',
       'check_in_or_checkout',
       
    ];

    /**
     *  Setup model event hooks
     */
   
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
    // public $timestamps = true;
}