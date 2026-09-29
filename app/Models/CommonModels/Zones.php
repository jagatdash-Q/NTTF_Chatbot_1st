<?php

namespace App\Models\CommonModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

use App\Models\CommonModels\Address;
use App\Models\CommonModels\ContactDetails;

class Zones extends Model
{
    use HasFactory ,SoftDeletes,LogsActivity;
    public $table = 'zones';
    protected $fillable = [
        'ZoneCode', 'uuid', 'CorporateCode', 'Corporate_id', 'ZoneName', 'ZoneShortName', 'ZoneEmail',
        'ZoneInchargename','zone_unit_type_id','zone_unit_type_name', 'ZoneInchargeContactNo', 'ZoneInchargeDesign', 'ZoneStatus', 'ZoneContact_id', 'ZoneAddress_id','center_category_id','center_name','center_program_id','program_name'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
       
    }

    public function contact()
    {
        return $this->belongsTo(ContactDetails::class, 'ZoneContact_id');
    }

    public function address()
    {
        return $this->belongsTo(Address::class, 'ZoneAddress_id');
    }
}
