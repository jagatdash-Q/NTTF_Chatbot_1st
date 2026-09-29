<?php

namespace App\Models\CoreProgrammeModels;

use App\Models\AcademicSectionModels\CollegeAcademicProgrammeStreams;
use App\Models\CommonModels\Centres;
use App\Models\CommonModels\FeeQuota;
use App\Models\CommonModels\StudentEnrollmentDocument;
use App\Models\CommonModels\StudentPayment;
use App\Models\CoreAdmissionCurrentAcademicYear;
use App\Models\QuotaMaster;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class StudentEnrollment extends Model
{
    use HasFactory, Notifiable, LogsActivity;
    public $table = "student_enrollments";
    protected $fillable = [
        'uuid',
        'student_code',
        'student_lead_id',
        'exam_result_id',
        'joining_date',
        'centre_id',
        'entry_year',
        'course_id',
        'lateral_entry',
        'year',
        'month',
        'semester',
        'fee_quota_id',
        'category',
        'gender',
        'bachelor_of_vocation',
        'nocn_ncc',
        'nttf_alumni',
        'section',
        'fee_plan',
        'fee_applicable',
        'status',
        'remark',
        'created_by',
        'updated_by',
        'academic_year_id',
        'total_applicable_semestars',
        'core_fee_plan_id',
        'active_core_student_fee_plan_details_log_id',
        'core_fee_total_amount',
        'is_fee_paid',
        'program_center_wise_fee_quota'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function getCenter(): HasOne
    {
        return $this->hasOne(Centres::class, 'id', 'centre_id')->with(['contact']);
    }

    public function getCourse(): HasOne
    {
        return $this->hasOne(CollegeAcademicProgrammeStreams::class, 'id', 'course_id');
    }

    public function getFeeQuota(): HasOne
    {
        return $this->hasOne(FeeQuota::class, 'id', 'fee_quota_id');
    }

     public function getFeeQuotaConfig(): HasOne
    {
        return $this->hasOne(QuotaMaster::class, 'id', 'fee_quota_id');
    }

    public function getCreatedBy(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function getUpdatedBy(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }
    public function getStudentLead(): HasOne
    {
        return $this->hasOne(Studentlead::class, 'id', 'student_lead_id');
    }
    public function getPaymentDetails(): HasMany
    {
        return $this->hasMany(StudentPayment::class, 'student_lead_id', 'student_lead_id')->with(['getBank', 'getCreatedBy']);
    }
    public function getDocument(): HasMany
    {
        return $this->hasMany(StudentEnrollmentDocument::class, 'student_enrollment_id', 'id')->with(['getDocumentFile', 'getCreatedBy']);
    }
    public function getAdmissionYearDetails(): HasOne
    {
        return $this->hasOne(CoreAdmissionCurrentAcademicYear::class, 'id', 'academic_year_id');
    }
}
