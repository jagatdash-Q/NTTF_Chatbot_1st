<?php

namespace App\Models\CoreProgrammeModels;

use App\Models\AcademicSectionModels\CollegeAcademicProgrammes;
use App\Models\AcademicSectionModels\CollegeAcademicProgrammeStreams;
use App\Models\CommonModels\Address;
use App\Models\CommonModels\CenterPrograme;
use App\Models\CommonModels\Centres;
use App\Models\CommonModels\HighestEducationalQualification;
use App\Models\CommonModels\LeadMaster;
use App\Models\CommonModels\MediaManager;
use App\Models\CommonModels\PaymentMode;
use App\Models\CommonModels\QuotaSystem;
use App\Models\CommonModels\StudentEducationDetails;
use App\Models\CommonModels\StudentFamily;
use App\Models\CommonModels\StudentPayment;
use App\Models\CommonModels\StudentReferral;
use App\Models\CommonModels\StudentWorkExperience;
use App\Models\CommonModels\TestTimeSlot;
use App\Models\CommonModels\UserIdentification;
use App\Models\CoreAdmissionCurrentAcademicYear;
use App\Models\CoreBatch;
use App\Models\CoreMidtermStudentMarkEntry;
use App\Models\CoreSemesterMarkEntry;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\CoreExamResults;
use App\Models\CoreFeePlans;
use App\Models\CoreStudentAttendanceForDemand;
use App\Models\CoreExamResultSupplymentary;

class StudentAdmission extends Model
{
    use HasFactory, Notifiable, SoftDeletes, LogsActivity;
    protected $appends = ['total_advance_amount'];
    protected $fillable = [
        'uuid',
        'email',
        'alternate_mobile',
        'student_lead_id',
        'address_id',
        'contact_details_id',
        'counseling_center_id',
        'time_slot_id',
        'payment_method_id',
        'quota_id',
        'highest_educational_qualification_id',
        'domicile_category',
        'gender',
        'media_manager_id',
        'medium_of_instruction',
        'student_admission_progress',
        'user_identification_id',
        'lead_source_id',
        'referred_by',
        'referer_mobile',
        'is_active',
        'wave_off',
        'wave_off_mail_sent',
        'wave_off_accept',
        'wave_off_action',
        'remarks',
        'branch',
        'category',
        'status',
        'moved_to_red',
        'created_by',
        'updated_by',

        'core_enquiry_date',
        'core_webform',
        'center_name',
        'center_id',
        'program_name',
        'program_id',
        'stream_name',
        'stream_id',
        'core_enquirycourse',
        'enquiry_year',
        'core_student_code',
        'core_student_temp_code',
        'batch_id',
        'batch_name',
        'current_term',
        'enrollment_course',
        'punching_code',
        'current_semester',
        'semester_no',
        'core_student_user_name',
        'core_student_password',
        'core_parent_user_name',
        'core_parent_password',

        'core_counsellor',
        'core_student_enquiry_mode',
        'core_referred_by',
        'core_source_to_nttf',
        'core_year',
        'core_quota',
        'core_enrollmenttags',
        'board_level_1',
        'institute_level_1',
        'location_level_1',
        'city_level_1',
        'language_level_1',
        'combinations_level_1',
        'board_level_2',
        'institute_level_2',
        'location_level_2',
        'city_level_2',
        'language_level_2',
        'combinations_level_2',
        'board_level_3',
        'institute_level_3',
        'location_level_3',
        'city_level_3',
        'language_level_3',
        'combinations_level_3',
        'board_level_4',
        'institute_level_4',
        'location_level_4',
        'city_level_4',
        'language_level_4',
        'combinations_level_4',
        'user_id',

        // Enrollment related details

        'lateral_entry',
        'fee_quota_id',
        'bachelor_of_vocation',
        'nocn_ncc',
        'nttf_alumni',
        'fee_applicable',
        'academic_year_id',
        'core_fee_plan_id',
        'active_core_student_fee_plan_details_log_id',
        'core_fee_total_amount',

        'created_with',
        'reporting_status',
        'residency_type',
        'eligible_for_provisional',
        'section_id', 'section_name', 'group_id', 'group_name',
        'term_condition',
        'status_remark',
        'nttf_diploma_holder',
        'nttf_diploma_rollno','certificate_course_duration','other_highest_qualification',
        'demand_reporting_status','is_maini','is_denso','is_tsf','nttf_email','is_azure',
        'current_admission_academic_year', 'is_nmdcdiploma','is_nmdccertificate',
        'is_rejoin',
        'is_stream_change'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function getStudentLead(): HasOne
    {
        return $this->hasOne(Studentlead::class, 'id', 'student_lead_id')->with(['getCenter', 'getPaymentTransactions']);
    }

    public function getUserIdentification(): HasOne
    {
        return $this->hasOne(UserIdentification::class, 'id', 'user_identification_id');
    }

    public function getApplicantPhoto(): HasOne
    {
        return $this->hasOne(MediaManager::class, 'id', 'media_manager_id');
    }

    public function getFamily(): HasMany
    {
        return $this->hasMany(StudentFamily::class, 'student_lead_id', 'student_lead_id')->with('getFamilyDetails');
    }

    public function getEducationDetails(): HasMany
    {
        return $this->hasMany(StudentEducationDetails::class, 'student_lead_id', 'student_lead_id');
    }

    public function getAddress(): HasOne
    {
        return $this->hasOne(Address::class, 'id', 'address_id')->where('address_type', 'Student');
    }
    public function getTimeSlot(): HasOne
    {
        return $this->hasOne(TestTimeSlot::class, 'id', 'time_slot_id');
    }
    public function getPaymentMode(): HasOne
    {
        return $this->hasOne(PaymentMode::class, 'id', 'payment_method_id');
    }
    public function getCourseCenterPreference(): HasMany
    {
        return $this->hasMany(StudentCentreCoursePreferences::class, 'student_lead_id', 'student_lead_id')->with(['getStream', 'getPreference1', 'getPreference2']);
    }
    public function getQuota(): HasOne
    {
        return $this->hasOne(QuotaSystem::class, 'id', 'quota_id');
    }
    public function getHighestEducational(): HasOne
    {
        return $this->hasOne(HighestEducationalQualification::class, 'id', 'highest_educational_qualification_id');
    }
    public function getLead(): HasOne
    {
        return $this->hasOne(LeadMaster::class, 'id', 'lead_source_id');
    }
    public function getBatchDetails(): HasOne
    {
        return $this->hasOne(CoreBatch::class, 'id', 'batch_id');
    }
    public function getStudentReferral(): HasOne
    {
        return $this->hasOne(StudentReferral::class, 'student_lead_id', 'student_lead_id');
    }
    public function getWorkExperience(): HasMany
    {
        return $this->hasMany(StudentWorkExperience::class, 'student_lead_id', 'student_lead_id');
    }

    public function getCenterDetails(): HasOne
    {
        return $this->hasOne(Centres::class, 'id', 'center_id');
    }

    public function getProgramDetails(): HasOne
    {
        return $this->hasOne(CollegeAcademicProgrammes::class, 'id', 'program_id');
    }

    public function getStreamDetails(): HasOne
    {
        return $this->hasOne(CollegeAcademicProgrammeStreams::class, 'id', 'stream_id');
    }
    public function results()
    {
        return $this->hasMany(CoreExamResults::class, 'core_student_id');
    }
    public function midResults()
    {
        return $this->hasMany(CoreMidtermStudentMarkEntry::class, 'core_student_id');
    }
    public function endResults()
    {
        return $this->hasMany(CoreSemesterMarkEntry::class, 'core_student_id');
    }
    public function getFeePlanDetails()
    {
        return $this->hasOne(CoreFeePlans::class, 'id', 'core_fee_plan_id');
    }

    public function getStudentEnrollmentDetails(): HasOne
    {
        return $this->hasOne(StudentEnrollment::class, 'student_lead_id', 'student_lead_id')->with(['getFeeQuotaConfig']);
    }

    public function getAcademicYearDetails(): HasOne
    {
        return $this->hasOne(CoreAdmissionCurrentAcademicYear::class, 'id', 'academic_year_id');
    }

    public function supplementaryResults()
    {
        return $this->hasMany(CoreExamResultSupplymentary::class,'student_id','id');
    }

    public function getSemesterAttendance(): HasOne
    {
        return $this->hasOne(CoreStudentAttendanceForDemand::class, 'student_lead_id', 'student_lead_id');
    }

    public function totalAdvancePayments(): HasMany
    {
        return $this->hasMany(StudentPayment::class, 'student_lead_id', 'student_lead_id')
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
            ]);
    }

    public function getTotalAdvanceAmountAttribute()
    {
        if ($this->relationLoaded('totalAdvancePayments')) {
            return $this->totalAdvancePayments->sum('fee_paid');
        }

        return $this->totalAdvancePayments()->sum('fee_paid');
    }

}
