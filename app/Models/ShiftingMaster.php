<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ShiftingMaster extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = [
       
        'shift_name',
        'shift_code',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'status',
        'created_by',
        'updated_by',
        'session_name',
          'core_zone_id',
                'center_id',
                'program_id',
                'academic_year_id',
                'year_month',
                'stream_id',
                'batch_id',
                'section_id',



    ];
   
    public $table = 'shifting_masters';
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
