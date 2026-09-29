<?php

namespace App\Models\CommonModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\CommonModels\Zones;


class ContactDetails extends Model
{
    use HasFactory ,SoftDeletes,LogsActivity;
    public $table = 'contact_details';
    protected $fillable = [
        'contact_value', 'person_name', 'person_email', 'person_designation',
        'is_primary', 'person_mobile_no', 'alternative_mobile_no', 'contact_for', 'contact_type', 'contact_status','industry_partner_id','full_address','industry_partner_id', 'billing_details_id'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
       
    }
    public function zone()
    {
        return $this->belongsTo(Zones::class);
    }
}
