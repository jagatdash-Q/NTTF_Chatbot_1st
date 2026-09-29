<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoreCreditToStudentFeeheadWiseLogs extends Model
{
    protected $fillable = [
        'uuid',
        'core_credit_to_students_id',
        'fee_head_id',
        'fee_head_name',
        'amount',
        'center_category',
        'is_credit',
        'sort_order',
        'pending_amount',
        'collected_amount',
        'created_with',
        'migrated_on',
        'semester',
        'student_lead_id'
    ];
}
