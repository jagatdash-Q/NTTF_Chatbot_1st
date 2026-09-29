<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


use App\Models\AcademicSectionModels\CollegeAcademicProgrammes;
use App\Models\AcademicSectionModels\Semesters;
use  \App\Models\CoreAdmissionCurrentAcademicYear;
use App\Http\Controllers\Admin\CoreAssessment\CoreSemesterStatsController;
use App\Models\AcademicSectionModels\CollegeAcademicProgrammeStreamBranches;
use App\Models\AcademicSectionModels\CollegeAcademicProgrammeStreams;
use App\Models\AcademicSectionModels\Subjects;


class SupplymentaryConfig extends Model
{


    protected $fillable = [
        'program_id',
        'stream_id',
        'semester_id',
        'academic_year',
        'subject_id',
        'maximum_marks',
        'passing_marks',
        'conversion_marks',
    ];

    // Example relations — adapt names to your actual models:
    public function program()
    {
        return $this->belongsTo(CollegeAcademicProgrammes::class, 'program_id');
    }

    public function stream()
    {
        return $this->belongsTo(CollegeAcademicProgrammeStreams::class, 'stream_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subjects::class, 'subject_id');
    }
    public function semester()
    {
        return $this->belongsTo(Semesters::class, 'semester_id');
    }
}
