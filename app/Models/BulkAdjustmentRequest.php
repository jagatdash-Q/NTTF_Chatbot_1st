<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BulkAdjustmentRequest extends Model
{
    protected $fillable = [
        'student_lead_id',
        'core_student_code',
        'receipt_no',
        'bulk_no',
        'request_by',
        'requested_on',
        'request_complete_on',
        'completion_status',
        'receipt_series',
        'total_edutech_pending',
        'total_nttf_pending',
        'total_advance',
        'remaining_advance_after_adjustment',
        'total_adjusted_amount',
        'remarks'
    ];
}
