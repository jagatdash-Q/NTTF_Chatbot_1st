<?php

namespace App\Models;

use App\Models\CommonModels\LegsZone;
use App\Models\IndustryPartnerModels\BillingDetail;
use App\Models\IndustryPartnerModels\IndustryPartnerMasterDetails;
use Illuminate\Database\Eloquent\Model;

class IndustryPartnerReimbersement extends Model
{
    protected $fillable = [
        'legs_zones_id',
        'industry_partner_master_details_id',
        'billing_plant_id',
        'reimbursement_of_uniform',
        'stationery',
        'welcome_kit',
        'safety_shoes',
        'course_material',
        'sourcing_fee',
        'selection_fee',
        'medical_insurance',
        'accidental_insurance',
        'workman_compenation',
        'created_by',
        'updated_by',
    ];

    public function getZone()
    {
        return $this->belongsTo(LegsZone::class, 'legs_zones_id', 'id');
    }

    /**
     * Get the Industry Partner for the reimbursement.
     */
    public function getIndustryPartner()
    {
        return $this->belongsTo(IndustryPartnerMasterDetails::class, 'industry_partner_master_details_id', 'id');
    }

    /**
     * Get the Billing Plant for the reimbursement.
     */
    public function getBillingDetails()
    {
        return $this->belongsTo(BillingDetail::class, 'billing_plant_id', 'id');
    }
}
