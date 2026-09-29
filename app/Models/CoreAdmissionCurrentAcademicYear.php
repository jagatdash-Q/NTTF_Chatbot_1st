<?php

namespace App\Models;

use App\Models\AcademicSectionModels\CollegeAcademicProgrammes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CoreAdmissionCurrentAcademicYear extends Model
{
    protected $fillable = [
        'academic_year',
        'academic_month',
        'program_type',
        'created_by',
        'updated_by',
        'desc',
        'status',
        'year_month',
        'admission_active'
    ];

    public function getProgramDetails(): HasOne
    {
        return $this->hasOne(CollegeAcademicProgrammes::class, 'id', 'program_type');
    }
}
