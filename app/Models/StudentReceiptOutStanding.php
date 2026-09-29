<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentReceiptOutStanding extends Model
{
    protected $fillable = [
        'student_lead_id',
        'student_code',
        'receipt_no',
        'balance_amount',
        'nttf_amount',
        'edutech_amount',
        'advance_amount',
    ];
}
