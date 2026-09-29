<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;

class CoreEvent extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'core_events';

    protected $fillable = [
        'company_id',
        'company_name',
        'plant_name',
        'event_date',
        'event_type',
        'hiring_location',
        'no_of_opening',
        'selection_venue',
        'date',
        'company_spoc',
        'created_by',
        'updated_by',
        'center_id', 
        'center_name',
        'event_name',
        'event_time',
        'hiring_id'
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
