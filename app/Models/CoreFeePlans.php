<?php

namespace App\Models;

use App\Models\AcademicSectionModels\CollegeAcademicProgrammes;
use App\Models\AcademicSectionModels\CollegeAcademicProgrammeStreams;
use App\Models\CommonModels\Centres;
use App\Models\CommonModels\FeeQuota;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CoreFeePlans extends Model
{
    protected $fillable = [
        'plan_name',
        'program',
        'centre_id',
        'quota_master_id',
        'current_admission_year_id',
        'course_id',
        'quota_system_id',
        'fee_quota',
        'category',
        'gender',
        'total_amount',
        'is_bvoc',
        'is_nocn_ncc',
        'is_nttf_alumni',
        'sem_1_total_amount',
        'sem_2_total_amount',
        'sem_3_total_amount',
        'sem_4_total_amount',
        'sem_5_total_amount',
        'sem_6_total_amount',
        'created_by',
        'updated_by',
        'sem_1_payment_date',
        'sem_2_payment_date',
        'sem_3_payment_date',
        'sem_4_payment_date',
        'sem_5_payment_date',
        'sem_6_payment_date',
        'lateral',

        'sem_1_remarks',
        'sem_2_remarks',
        'sem_3_remarks',
        'sem_4_remarks',
        'sem_5_remarks',
        'sem_6_remarks',
    ];

    public function getProgramDetails(): HasOne
    {
        return $this->hasOne(CollegeAcademicProgrammes::class, 'id', 'program');
    }

    public function quotaConfigDetails(): HasOne
    {
        return $this->hasOne(QuotaMaster::class, 'id', 'quota_master_id');
    }

    public function currentAdmissionAcademicYear(): HasOne
    {
        return $this->hasOne(CoreAdmissionCurrentAcademicYear::class, 'id', 'current_admission_year_id');
    }

    public function getStreamDetils(): HasOne
    {
        return $this->hasOne(CollegeAcademicProgrammeStreams::class, 'id', 'course_id');
    }

    public function getFeeQuotaDetails(): HasOne
    {
        return $this->hasOne(FeeQuota::class, 'id', 'fee_quota');
    }

    public function getCenterDetails(): HasOne
    {
        return $this->hasOne(Centres::class, 'id', 'centre_id');
    }

    public function semesterDetails(): HasMany
    {
        return $this->hasMany(CoreFeePlanSemesterWiseDetails::class, 'core_fee_plan_id', 'id')->with('getFeeHeadDetails');
    }
}
