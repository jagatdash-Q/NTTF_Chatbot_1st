<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CoreCenterwiseClassvenu extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = [
       
        'core_zone_id',
        'core_center_id',
        'class_venue',
        'class_room_name',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'status',
        'created_by',
        'updated_by'



    ];
   
    public $table = 'core_centerwise_classvenus';
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
