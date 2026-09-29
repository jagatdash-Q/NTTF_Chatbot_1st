<?php

namespace App\Models\CommonModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

use App\Models\CommonModels\Address;
use App\Models\CommonModels\ContactDetails;

class ZoneUnitTypes extends Model
{
    use HasFactory ,LogsActivity;
    public $table = 'zone_unit_types';
    protected $fillable = [
       'name','description','uuid','status'
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
       
    }
}
