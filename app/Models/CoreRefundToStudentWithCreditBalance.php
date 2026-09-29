<?php

namespace App\Models;

use App\Models\CommonModels\Bank;
use App\Models\CommonModels\Centres;
use App\Models\CommonModels\PaymentMode;
use App\Models\CoreProgrammeModels\StudentAdmission;
use App\Models\CoreProgrammeModels\Studentlead;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CoreRefundToStudentWithCreditBalance extends Model
{
    protected $table = 'core_refund_to_student_with_credit_balances';
    protected $fillable = [
        'uuid',
        'reciept_no',
        'student_lead_id',
        'created_by',
        'updated_by',
        'collected_amount',
        'payment_method_id',
        'bank_id',
        'branch_name',
        'instrument_date',
        'instrument_identifier',
        'payment_centre',
        'deposit_bank',
        'receipt_series',
        'remark',
        'is_reverted',
        'is_reverted_transaction',
        'created_with',
        'semester'
    ];

    public function getPaymentCenter(): HasOne
    {
        return $this->hasOne(Centres::class, 'id', 'payment_centre')->with('address');
    }

    public function getStudentLeadDetails(): HasOne
    {
        return $this->hasOne(Studentlead::class, 'id', 'student_lead_id')->with('getCompleteAdmissionDetails');
    }

    public function getBank(): HasOne
    {
        return $this->hasOne(Bank::class, 'id', 'bank_id');
    }

    public function getPaymentMethod(): HasOne
    {
        return $this->hasOne(PaymentMode::class, 'id', 'payment_method_id');
    }

    public function collector()
    {
        return $this->belongsTo(\App\Models\User::class, 'collected_by');
    }

    public function getfeeHeads(): HasMany
    {
        return $this->hasMany(CoreRefundToStudentWithCreditBalanceFeeHeadCollectionLogs::class, 'core_refund_to_student_with_credit_balances_id', 'id');
    }

    public function getAdvancefeeHeads(): HasOne
    {
        return $this->hasOne(CoreRefundWithCreditBalanceAdvanceLogs::class, 'core_refund_to_student_with_credit_balances_id', 'id');
    }

    public function getAdmissionDetails(): HasOne
    {
        return $this->hasOne(StudentAdmission::class, 'student_lead_id', 'student_lead_id')->with(['getCourseCenterPreference']);
    }
}
