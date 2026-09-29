<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoreRefundToStudentWithCreditBalanceFeeHeadCollectionLogs extends Model
{
    protected $table = 'core_refund_to_student_with_credit_fee_head_collection_logs';
    protected $fillable = [
        'core_credit_to_students_id',
        'core_credit_to_student_feehead_wise_logs_id',
        'fee_head_id',
        'fee_head_name',
        'pending_amount',
        'paid_now_amount',
        'center_category',
        'is_credit',
        'sort_order',
        'created_by',
        'updated_by',
        'core_refund_to_student_with_credit_balances_id',
        'is_reverted',
        'is_reverted_transaction',
        'created_with',
        'term',
        'import_id',
        'migrated_on',
        'semester',
        'student_lead_id'
    ];
}
