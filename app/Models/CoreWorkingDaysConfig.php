<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;

class CoreWorkingDaysConfig extends Model
{
    use HasFactory;

   protected $fillable = [
        'day_name',
        'is_working',
        'start_time',
        'end_time',
    ];

   
     public $table = 'core_working_days_configs';
   
}
