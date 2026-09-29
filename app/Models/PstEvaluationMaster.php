<?php

namespace App\Models;

use App\Models\CommonModels\LegsZone;
use App\Models\IndustryPartnerModels\IndustryPartnerMasterDetails;
use App\Models\LegsProgrammeModels\LegsCollegeAcademicProgrammeStreams;
use App\Models\LegsProgrammeModels\LegsSemesters;
use App\Models\LegsProgrammeModels\LegsStudentBatch;
use Illuminate\Database\Eloquent\Model;

class PstEvaluationMaster extends Model
{
    protected $fillable = [
        'legs_zone_id',
        'industry_partner_id',
        'batch_id',
        'program_id',
        'semester_id',
        'month',
        'year',
    ];

    public function legsZone()
    {
        return $this->belongsTo(LegsZone::class);
    }

    public function industryPartner()
    {
        return $this->belongsTo(IndustryPartnerMasterDetails::class);
    }

    public function batch()
    {
        return $this->belongsTo(LegsStudentBatch::class);
    }

    public function program()
    {
        return $this->belongsTo(LegsCollegeAcademicProgrammeStreams::class);
    }

    public function semester()
    {
        return $this->belongsTo(LegsSemesters::class);
    }

    public function evaluations()
    {
        return $this->hasMany(Pstevaluation::class, 'master_id');
    }
}
