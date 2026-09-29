<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;

class CoreHoliday extends Model
{
    use HasFactory, LogsActivity;

    // Specify table name explicitly
    protected $table = 'core_holidays';

    // Mass assignable fields
    protected $fillable = [
        'holiday_date',
        'holiday_name',
        'description',
        'created_by',      
        'updated_by',
        'zone_id', 'center_id', 'day'
       
    ];
     public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
