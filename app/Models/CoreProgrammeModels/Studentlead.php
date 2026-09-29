<?php

namespace App\Models\CoreProgrammeModels;

use App\Models\AcademicSectionModels\CollegeAcademicProgrammes;
use App\Models\CommonModels\Centres;
use App\Models\CommonModels\StudentEducationDetails;
use App\Models\CommonModels\StudentReferral;
use App\Models\CommonModels\UserIdentification;
use App\Models\CoreStudentAdditionalInfo;
use App\Models\QuizModels\ExamResults;
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
use App\Models\CoreStudentAdmissionStatusChangeLog;

class Studentlead extends Model
{
    use HasFactory, Notifiable, SoftDeletes, LogsActivity;
    public $table = "student_leads";
    protected $fillable = [
        'uuid',
        'program_type',
        'ticket_number',
        'applicant_name',
        'dob',
        'center_core_program_id',
        'college_academic_program_stream_branch_id',
        'refer_code',
        'mobile',
        'lateral_entry',
        'created_by',
        'updated_by',
        'status',
        'enquiry_number',
        'created_with'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function getCenter(): HasOne
    {
        return $this->hasOne(Centres::class, 'id', 'center_core_program_id')->with('address');
    }

    public function getProgramDetails(): HasOne
    {
        return $this->hasOne(CollegeAcademicProgrammes::class, 'id', 'program_type');
    }

    public function getStudentEnrollmentDetails(): HasOne
    {
        return $this->hasOne(StudentEnrollment::class, 'student_lead_id', 'id')->with(['getFeeQuotaConfig','getCenter']);
    }

    public function getCompleteAdmissionDetails(): HasOne
    {
        return $this->hasOne(StudentAdmission::class, 'student_lead_id', 'id')->with(['getQuota', 'getHighestEducational', 'getLead','getUserIdentification','getApplicantPhoto','getFamily','getEducationDetails','getAddress','getCenterDetails','getProgramDetails','getStreamDetails']);
    }


    public function getFullAdmission(): HasOne
    {
        return $this->hasOne(StudentAdmission::class, 'student_lead_id', 'id')
            ->with(['getQuota', 'getHighestEducational', 'getLead','getBatchDetails','getCenterDetails','getProgramDetails','getStreamDetails']);
    }

    // public function getFullAdmission(): HasOne
    // {
    //     return $this->hasOne(StudentAdmission::class, 'student_lead_id', 'id')
    //         ->where('status', 'full_admission_taken')
    //         ->with(['getQuota', 'getHighestEducational', 'getLead','getBatchDetails','getCenterDetails','getProgramDetails','getStreamDetails']);
    // }


    public function getStudentAdmission(): HasOne
    {
        return $this->hasOne(StudentAdmission::class, 'student_lead_id', 'id')->with(['getQuota', 'getHighestEducational', 'getLead','getTimeSlot']);
    }

    public function getEducationDetails(): HasMany
    {
        return $this->hasMany(StudentEducationDetails::class, 'student_lead_id', 'id')->orderBy('id', 'desc');
    }

    public function getReferral(): HasOne
    {
        return $this->hasOne(StudentReferral::class, 'student_lead_id', 'id');
    }

    public function getExamResult(): HasOne
    {
        return $this->hasOne(ExamResults::class, 'student_id', 'id');
    }
    public function getPaymentTransactions(): HasOne
    {
        return $this->hasOne(StudentPaymentTransactions::class, 'student_id', 'ticket_number')->orderBy('id', 'desc');
    }

    public function getPlacementStatus(): HasOne
    {
        return $this->hasOne(CoreStudentAdditionalInfo::class, 'student_lead_id', 'id');
    }
    public function getStudentAdmissionStatusChangeLog(): HasMany
    {
        return $this->hasMany(CoreStudentAdmissionStatusChangeLog::class, 'student_lead_id', 'id');
    }
    public function getPreference(): HasMany
    {
        return $this->hasMany(StudentCentreCoursePreferences::class, 'student_lead_id', 'id');
    }
}
