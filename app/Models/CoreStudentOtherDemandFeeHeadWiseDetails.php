<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoreStudentOtherDemandFeeHeadWiseDetails extends Model
{
    protected $fillable = [
        'core_student_other_demand_active_details_id',
        'fee_head_id',
        'fee_head_category',
        'fee_head_name',
        'fee_head_amount',
        'updated_by',
        'center_category',
        'sort_order',
        'total_collected_amount',
        'total_pending_amount',
        'is_reverted',
        'demand_reverse_remarks',
        'created_with',
        'student_lead_id',
        'migrated_on',
        'semester',
        'demand_reverse_date',
        'tally_code',
        'demand_type'
    ];

    public function collections()
    {
        return $this->hasMany(CollectOtherStudentDemand::class, 'core_student_other_demand_fee_head_wise_details_id');
    }

    public function getCollectedAmountAttribute()
    {
        return $this->collections()->sum('collection_amount');
    }

    public function getRemainingAmountAttribute()
    {
        return $this->fee_head_amount - $this->collected_amount;
    }

    public function getActiveDetails(){
        return $this->hasOne(CoreStudentOtherDemandActiveDetails::class, 'id','core_student_other_demand_active_details_id')->with(['getDemandDetails']);
    }
}
