<?php

namespace App\Models;

use App\Models\CommonModels\Bank;
use App\Models\CommonModels\Centres;
use App\Models\CommonModels\PaymentMode;
use App\Models\CoreProgrammeModels\Studentlead;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CoreRefundToStudentWithOutCreditBalanceLogs extends Model
{
    protected $table = 'core_refund_to_student_logs_with_out_credit_balance';
    protected $fillable = [
        'core_credit_to_student_id',
        'previous_amount',
        'updated_amount',
        'updated_reciept_no',
        'collected_by',
        'payment_method_id',
        'bank_id',
        'branch_name',
        'instrument_date',
        'instrument_identifier',
        'payment_centre',
        'deposit_bank',
        'receipt_series',
        'remark',
        'student_lead_id',
        'is_reverted_transaction',
        'is_reverted',
        'is_paid_from_advance',
        'semester'
    ];

    public function getPaymentCenter(): HasOne
    {
        return $this->hasOne(Centres::class, 'id', 'payment_centre')->with(['address','contact']);
    }

    public function getStudentLeadDetails(): HasOne
    {
        return $this->hasOne(Studentlead::class, 'id', 'student_lead_id')->with('getCompleteAdmissionDetails');
    }

    public function getBank(): HasOne
    {
        return $this->hasOne(Bank::class, 'id', 'bank_id');
    }

    public function RefundWithOutCreditDetails(): HasOne
    {
        return $this->hasOne(CoreRefundToStudentWithOutCreditBalance::class, 'id', 'core_credit_to_student_id');
    }

    public function getPaymentMethod(): HasOne
    {
        return $this->hasOne(PaymentMode::class, 'id', 'payment_method_id');
    }

    public function collector()
    {
        return $this->belongsTo(\App\Models\User::class, 'collected_by');
    }

    public function getCollections(){
        return $this->hasMany(CoreRefundToStudentWithoutCreditBalanceFeeHeadWiseCollectionLogs::class, 'core_refund_to_student_without_credit_collection_id', 'id');
    }
}
