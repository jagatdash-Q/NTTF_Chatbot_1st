<?php

namespace App\Models\IndustryPartnerModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\CommonModels\Address;
use App\Models\CommonModels\ContactDetails;
use App\Models\CommonModels\MediaManager;

class IndustryPartnerMasterDetails extends Model
{
    use HasFactory,LogsActivity;
    protected $fillable = [
        'id',
        //'uuid',
        'IPDetails_code',
        'Centre_code',
        'centre_master_id',
        'CentreDetails_Type',
        'IP_Sector',
        'contact_details_id',
        'IP_name',
        'centre_cat_id',
        'ip_sector_id',
        'IP_ProductManuf',
        'IP_KeyProcess',
        'created_on',
        'address_id',
        'address',
        'Media_manager_id',
        'created_by',
        'modified_on',
        'modified_by',
        'contact_type',
        'contact_status',
        'plants_covered_id',
        'center_program_category_id',
        'IP_MoURefNo',
        'IP_ContractSignDate',
        'IP_ContractValidity',
        'IP_CommencementDate',
        'no_of_plants',
        'company_website',
        'legs_zone_id',
        'po_ref_name',
        'po_document',
        'mou_document'
    ];
    public $table = 'industry_partner_master_details';

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
    public function mediamanager()
    {
        return $this->belongsTo(MediaManager::class, 'media_manager_id');
    }
}
