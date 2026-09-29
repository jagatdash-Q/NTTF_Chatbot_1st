<?php

namespace App\Models;

use App\Models\CommonModels\StudentPayment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectOtherStudentDemand extends Model
{
    use HasFactory;

    protected $fillable = [
        'core_student_other_demand_fee_head_wise_details_id',
        'collection_amount',
        'remaining_balance',
        'collection_reference_no',
        'collected_by',
        'collected_at',
        'is_reverted',
        'is_reverted_transaction',
        'student_lead_id',
        'is_paid_from_advance',
        'created_with',
        'is_additional_collection',
        'migrated_on',
        'semester',
        'payment_center',
        'collection_type'
    ];

    protected $casts = [
        'collected_at' => 'datetime',
    ];

    // Relationship: optional, just integer reference
    public function feeHeadDetail()
    {
        return $this->belongsTo(CoreStudentOtherDemandFeeHeadWiseDetails::class, 'core_student_other_demand_fee_head_wise_details_id');
    }

    public function getStudentPaymentDetails(){
        return $this->hasOne(StudentPayment::class,'reciept_no', 'collection_reference_no');
    }

    public function getActiveDemandDetails(){
        return $this->hasOne(CoreStudentOtherDemandFeeHeadWiseDetails::class, 'core_student_other_demand_fee_head_wise_details_id')->with(['getActiveDetails']);
    }
}
