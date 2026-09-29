<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoreStudentFeePlanSemestarWiseFeeHeadDetailLog extends Model
{
    protected $fillable = [
        'core_student_fee_plan_details_log_id',
        'semester',
        'total_amount',
        'fee_head_name',
        'payment_date',
        'core_fee_plan_semester_wise_details_id',
        'fee_head_id',
        'center_category',
        'sort_order',
        'total_collected_amount',
        'total_pending_amount',
        'student_lead_id'
    ];
}
