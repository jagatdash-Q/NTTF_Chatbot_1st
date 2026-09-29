<?php

namespace App\Models;

use App\Models\AcademicSectionModels\CollegeAcademicProgrammes;
use App\Models\AcademicSectionModels\CollegeAcademicProgrammeStreams;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CoreProgramSemesterDemandDate extends Model
{
    protected $fillable = [
        'program_type',
        'course',
        'academic_year_id',
        'academic_year_month',
        'created_by',
        'updated_by',
        'semester',
        'demand_date',
    ];

    public function getProgramDetails(): HasOne
    {
        return $this->hasOne(CollegeAcademicProgrammes::class, 'id', 'program_type');
    }

    public function getCourseDetails(): HasOne
    {
        return $this->hasOne(CollegeAcademicProgrammeStreams::class, 'id', 'course');
    }

    public function getCurentAdmissionYear(): HasOne
    {
        return $this->hasOne(CoreAdmissionCurrentAcademicYear::class, 'id', 'academic_year_id');
    }
}
