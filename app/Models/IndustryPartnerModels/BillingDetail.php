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

class BillingDetail extends Model
{
    use HasFactory,LogsActivity;
    protected $table = 'billing_details';

    protected $fillable = [
        'industrypartner_master_id',
        'industry_plant_name',
        'billing_address_line1',
        'billing_address_line2',
        'billing_city',
        'billing_state_or_province',
        'billing_district',
        'billing_pincode',
        'invoice_person_name',
        'invoice_person_email_id',
        'invoice_person_designation',
        'remarks',
        'entry_date',
        'products_manufactured',
        'establishment_year',
        'company_website',
        'pan_number',
        'tan_number',
        'bank_name',
        'service_tax_registration_no',
        'gst_number',
        'turn_over_crores',
        'employee_size',
        'current_status',
        'empanelment_status',
        'contact_person_name',
        'contact_person_designation',
        'contact_person_mobile_no',
        'contact_person_email',
        'plant_unionised',
        'no_of_employees_regular',
        'no_of_employees_contractual',
        'no_of_employees_apprentice',
        'no_of_class_room_available',
        'is_lab_available',
        'no_of_shifts',
        'status',
        'plant_location',
        'plant_head_name',
        'po_ref_name',
        'po_document',
        'mou_ref_name',
        'mou_document',
        'IP_ContractValidity',
        'IP_CommencementDate',
        'created_by',
        'updated_by',
        'latitude',
        'longitude',
        'full_address'
       
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
       
    }
}
