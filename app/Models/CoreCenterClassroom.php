<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;

class CoreCenterClassroom extends Model
{
    use HasFactory, LogsActivity;

   
    protected $table = 'core_center_classroom';

    
    protected $fillable = [
        'classroom_name',
        'center_id',
        'venue_name',
        'description',
         'created_by',
        'updated_by',  
        'classroom_short_name',
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

   
}
