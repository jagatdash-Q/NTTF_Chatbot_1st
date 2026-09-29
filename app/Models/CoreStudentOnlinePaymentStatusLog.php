<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoreStudentOnlinePaymentStatusLog extends Model
{
    protected $fillable = [
        'student_lead_id',
        'txnid',
        'student_payment_transaction_id',
    ];
}
