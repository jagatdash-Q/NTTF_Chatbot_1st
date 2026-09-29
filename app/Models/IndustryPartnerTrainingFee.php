<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\CommonModels\LegsZone;
use App\Models\IndustryPartnerModels\BillingDetail;
use App\Models\IndustryPartnerModels\IndustryPartnerMasterDetails;
class IndustryPartnerTrainingFee extends Model
{
    protected $fillable = [
        'legs_zones_id',
        'industry_partner_master_details_id',
        'billing_plant_id',
        'monthly_training_fee_to_nttf',
        'stationary',
        'welcome_kit',
        'course_material',
        'selection_fee',
        'sourcing_fee',
        'rate',
        'minimum_batch_size',
        'lumsum',
        'validity_date',
        'remarks',
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
