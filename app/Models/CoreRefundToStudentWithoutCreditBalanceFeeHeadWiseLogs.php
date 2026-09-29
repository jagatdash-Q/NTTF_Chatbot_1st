<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoreRefundToStudentWithoutCreditBalanceFeeHeadWiseLogs extends Model
{
    protected $table = 'core_refund_to_student_without_credit_balance_fee_head_wise_logs';
    protected $fillable = [
        'core_refund_to_student_without_credit_balance_id',
        'fee_head_id',
        'fee_head_name',
        'total_amount',
        'pending_amount',
        'paid_amount',
        'center_category',
        'is_credit',
        'sort_order',
        'created_by',
        'updated_by',
        'semester',
        'student_lead_id'
    ];
}
