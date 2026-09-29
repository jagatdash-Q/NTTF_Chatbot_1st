<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoreRefundWithCreditBalanceAdvanceLogs extends Model
{
    protected $fillable = [
        'core_refund_to_student_with_credit_balances_id',
        'collected_amount',
        'fee_head_name',
        'created_by',
        'updated_by',
        'is_reverted',
        'is_reverted_transaction',
        'semester',
        'student_lead_id'
    ];
}
