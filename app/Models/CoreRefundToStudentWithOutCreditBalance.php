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

class CoreRefundToStudentWithOutCreditBalance extends Model
{
    protected $table = 'core_refund_to_students_with_out_credit_balance';
    protected $fillable = [
        'student_lead_id',
        'student_code',
        'reciept_no',
        'given_by',
        'refund_date',
        'total_amount_given',
        'updated_amount',
        'purpose',
        'pending_amount',
        'payment_method_id',
        'bank_id',
        'branch_name',
        'instrument_date',
        'instrument_identifier',
        'payment_centre',
        'deposit_bank',
        'receipt_series',
        'remark',
        'semester'
    ];

    public function getStudentDetails(): HasOne
    {
        return $this->hasOne(Studentlead::class, 'id', 'student_lead_id');
    }

    public function getBank(): HasOne
    {
        return $this->hasOne(Bank::class, 'id', 'bank_id');
    }

    public function getStudentLeadDetails(): HasOne
    {
        return $this->hasOne(Studentlead::class, 'id', 'student_lead_id')->with('getCompleteAdmissionDetails');
    }

    public function getAdmissionDetails(): HasOne
    {
        return $this->hasOne(StudentAdmission::class, 'student_lead_id', 'student_lead_id')->with(['getCourseCenterPreference']);
    }

    public function getCreatedBy(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function getPaymentCenter(): HasOne
    {
        return $this->hasOne(Centres::class, 'id', 'payment_centre')->with(['address','contact']);
    }

    public function getPaymentMethod(): HasOne
    {
        return $this->hasOne(PaymentMode::class, 'id', 'payment_method_id');
    }

    public function getRefundToStudentWithOutCreditBalanceLogs(): HasMany
    {
        return $this->hasMany(CoreRefundToStudentWithOutCreditBalanceLogs::class, 'core_credit_to_student_id', 'id')->with(['getPaymentMethod','getPaymentCenter']);
    }

    public function getApplicableFeeHeads(): HasMany
    {
        return $this->hasMany(CoreRefundToStudentWithoutCreditBalanceFeeHeadWiseLogs::class, 'core_refund_to_student_without_credit_balance_id', 'id')->orderBy('sort_order');
    }
}
