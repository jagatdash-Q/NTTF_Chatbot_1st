<?php

namespace App\Models;

use App\Models\CommonModels\StudentPayment;
use Illuminate\Database\Eloquent\Model;

class CoreRefundToStudentWithoutCreditBalanceFeeHeadWiseCollectionLogs extends Model
{
    protected $table = 'core_refund_to_student_without_credit_fee_head_collection_logs';
    protected $fillable = [
        'core_refund_to_student_without_credit_logs_id',
        'core_refund_to_student_without_credit_collection_id',
        'fee_head_wise_logs_id',
        'reciept_no',
        'fee_head_id',
        'fee_head_name',
        'pending_amount',
        'paid_amount',
        'remaining_amount',
        'center_category',
        'is_credit',
        'sort_order',
        'created_by',
        'updated_by',
        'student_lead_id',
        'semester'
    ];

    public function getStudentPaymentDetails(){
        return $this->hasOne(StudentPayment::class,'reciept_no', 'reciept_no');
    }

    public function getCreditBalanceLogs(){
        return $this->hasOne(CoreRefundToStudentWithOutCreditBalanceLogs::class,'id', 'core_refund_to_student_without_credit_collection_id');
    }
}
