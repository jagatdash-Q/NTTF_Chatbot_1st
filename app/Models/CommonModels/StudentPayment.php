<?php

namespace App\Models\CommonModels;

use App\Models\CoreProgrammeModels\StudentAdmission;
use App\Models\CoreProgrammeModels\StudentEnrollment;
use App\Models\CoreProgrammeModels\Studentlead;
use App\Models\StudentEnrollmentLogs;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class StudentPayment extends Model
{
    use HasFactory, Notifiable, LogsActivity;
    public $table = "student_payments";
    // protected $appends = ['total_advance_amount'];
    protected $fillable = [
        'uuid',
        'student_lead_id',
        'student_enrollment_id',
        'payment_method_id',
        'bank_id',
        'branch_name',
        'instrument_date',
        'instrument_identifier',
        'payment_centre',
        'deposit_bank',
        'receipt_series',
        'fee_paid',
        'payment_date',
        'created_by',
        'updated_by',
        'remark',
        'payment_type',
        'student_enrollment_log_id',
        'reciept_no',
        'total_reciept_download_times',
        'core_credit_to_students_id',
        'core_credit_to_student_logs_id',
        'is_reverted',
        'is_reverted_transaction',
        'student_payments',
        'migrated_on',
        'semester',
        'academic_year_id',
        'is_paid_from_advance',
        'is_online_payment'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
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
        return $this->hasOne(StudentAdmission::class, 'student_lead_id', 'student_lead_id')->with(['getCourseCenterPreference', 'getStreamDetails']);
    }

    public function getCreatedBy(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function getPaymentCenter(): HasOne
    {
        return $this->hasOne(Centres::class, 'id', 'payment_centre')->with(['address', 'contact']);
    }

    public function getPaymentMethod(): HasOne
    {
        return $this->hasOne(PaymentMode::class, 'id', 'payment_method_id');
    }

    public function getCurrentEnrollmentDetails(): HasOne
    {
        return $this->hasOne(StudentEnrollment::class, 'student_lead_id', 'student_lead_id')->with([
            'getCenter',
            'getCourse',
            'getAdmissionYearDetails'
        ]);
    }

    public function getEnrollmentDetails(): HasOne
    {
        return $this->hasOne(StudentEnrollmentLogs::class, 'id', 'student_enrollment_log_id')->with([
            'getCenter',
            'getCourse',
            'getAdmissionYearDetails'
        ]);
    }

    public function getFullAdmission(): HasOne
    {
        return $this->hasOne(StudentAdmission::class, 'student_lead_id', 'student_lead_id')
            ->with(['getQuota', 'getHighestEducational', 'getLead', 'getBatchDetails', 'getCenterDetails', 'getProgramDetails', 'getStreamDetails']);
    }

    public static function getAdvanceAmount($studentLeadId)
    {
        return self::where('student_lead_id', $studentLeadId)
            ->whereIn('payment_type', [
                "temporary_enroll",
                "provisionally_enroll",
                "advance_payment",
                "advance_fee_head_adjustment",
                "advance_payment_refund",
                "advance_payment_refund_reverse",
                "advance_payment_reverse",
                // "credit_to_student_with_out_credit_balance",
                // "credit_to_student_with_out_credit_balance_refund",
                "demand_advance",
                // "credit_to_student_with_out_credit_balance_refund_reverse",
                "credit_to_student",
                "refund_to_student_with_credit_balance",
                "refund_to_student_with_credit_balance_reverse",
                "demand_advance_reverse"
            ])
            ->sum('fee_paid');
    }
}
