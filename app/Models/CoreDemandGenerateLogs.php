<?php

namespace App\Models;

use App\Models\AcademicSectionModels\CollegeAcademicProgrammes;
use App\Models\AcademicSectionModels\CollegeAcademicProgrammeStreams;
use App\Models\CommonModels\Centres;
use App\Models\CoreProgrammeModels\StudentAdmission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CoreDemandGenerateLogs extends Model
{
    protected $fillable = [
        'demand_generate_on',
        'demand_generate_by',
        'semestar_no',
        'total_template_amount',
        'sem_total_amount',
        'course',
        'program',
        'center',
        'created_by',
        'lateral_entry',
        'demand_code',
        'plan_id',
        'uuid',
        'admission_year_id',
        'demand_semester',
        'is_demand_reverted',
        'demand_reverse_remarks',
        'demand_reverse_date'
    ];


    public function getCenterDetails(): HasOne
    {
        return $this->hasOne(Centres::class, 'id', 'center');
    }

    public function getCourseDetails(): HasOne
    {
        return $this->hasOne(CollegeAcademicProgrammeStreams::class, 'id', 'course');
    }

    public function getProgramDetails(): HasOne
    {
        return $this->hasOne(CollegeAcademicProgrammes::class, 'id', 'program');
    }

    public function getFeePlanDetails(): HasOne
    {
        return $this->hasOne(CoreFeePlans::class, 'id', 'plan_id');
    }

    public function getDemandStudentList(): HasMany
    {
        return $this->hasMany(DemandStudentLogs::class, 'demand_id', 'id')->with(['getStudentAdmissionDetails','getStudentLeadDetails']);
    }
}
