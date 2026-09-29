<?php

namespace App\Models;

use App\Models\CoreProgrammeModels\StudentAdmission;
use App\Models\CoreProgrammeModels\StudentEnrollment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CoreStudentFeePlanDetailLog extends Model
{
    protected $fillable = [
        'student_name',
        'student_lead_id',
        'student_admission_id',
        'student_enrolment_id',
        'plan_name',
        'program_name',
        'centre_name',
        'centre_code',
        'course_name',
        'quota_name',
        'current_admission_year_month_details',
        'fee_quota',
        'category',
        'gender',
        'total_amount',
        'is_bvoc',
        'is_nocn_ncc',
        'is_nttf_alumni',

        'plan_id',
        'center_id',
        'program_id',
        'course_id',
        'sem_1_due_date',
        'sem_2_due_date',
        'sem_3_due_date',
        'sem_4_due_date',
        'sem_5_due_date',
        'sem_6_due_date',
        'sem_1_remark',
        'sem_2_remark',
        'sem_3_remark',
        'sem_4_remark',
        'sem_5_remark',
        'sem_6_remark',
        'sem_1_total_amount',
        'sem_2_total_amount',
        'sem_3_total_amount',
        'sem_4_total_amount',
        'sem_5_total_amount',
        'sem_6_total_amount',
    ];

    public function semWiseHeadDetails(): HasMany
    {
        return $this->hasMany(CoreStudentFeePlanSemestarWiseFeeHeadDetailLog::class, 'core_student_fee_plan_details_log_id', 'id');
    }

    public function getFullAdmission(): HasOne
    {
        return $this->hasOne(StudentAdmission::class, 'student_lead_id', 'student_lead_id')
            ->with(['getQuota', 'getHighestEducational', 'getLead', 'getBatchDetails', 'getCenterDetails', 'getProgramDetails', 'getStreamDetails','getStudentLead']);
    }

    public function getEnrollmentDetails(): HasOne
    {
        return $this->hasOne(StudentEnrollment::class, 'student_lead_id', 'student_lead_id')->with(['getFeeQuotaConfig']);
    }
}
