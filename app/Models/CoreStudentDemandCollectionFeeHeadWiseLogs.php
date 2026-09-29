<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CoreStudentDemandCollectionFeeHeadWiseLogs extends Model
{
    protected $fillable = [
        'core_student_demand_collection_logs_id',
        'demand_id',
        'core_student_fee_plan_detail_logs',
        'core_student_fee_plan_semestar_wise_fee_head_detail_logs',
        'total_amount',
        'collection_amount',
        'remaining_amount',
        'collected_by',
        'fee_head_id',
        'fee_head_name',
        'center_category',
        'sort_order',
        'is_paid_from_advance',
        'student_lead_id',
        'semester',
        'is_reverted',
        'is_reverted_transaction'
    ];

    public function getDemandCollectionLogDetails(): HasOne
    {
        return $this->hasOne(CoreStudentDemandCollectionLogs::class)->with(['getPaymentMethod']);
    }

}
