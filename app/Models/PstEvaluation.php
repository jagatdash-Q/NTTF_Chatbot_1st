<?php

namespace App\Models;

use App\Models\CommonModels\LegsZone;
use App\Models\IndustryPartnerModels\IndustryPartnerMasterDetails;
use App\Models\LegsProgrammeModels\LegsCollegeAcademicProgrammeStreams;
use App\Models\LegsProgrammeModels\LegsSemesters;
use App\Models\LegsProgrammeModels\LegsStudentBatch;
use App\Models\LegsProgrammeModels\StudentAdmissionLegs;
use Illuminate\Database\Eloquent\Model;

class PstEvaluation extends Model
{
    protected $fillable = [
        'master_id',
        'student_id',
        'department',
        'no_of_leave',
        'leave_score',
        'no_of_incident',
        'incident_score',
        'no_of_violation',
        'violation_score',
        'no_of_suggestion',
        'suggestion_score',
        'housekeeping',
        'housekeeping_score',
        'productivity',
        'productivity_score',
        'quality',
        'quality_score',
        'compliance',
        'compliance_score',
        'learning',
        'learning_score',
        'product_knowledge',
        'product_knowledge_score',
        'total_score',
        'scaled_score'
    ];

    protected $casts = [
        'no_of_leave' => 'integer',
        'leave_score' => 'integer',
        'no_of_incident' => 'integer',
        'incident_score' => 'integer',
        'no_of_violation' => 'integer',
        'violation_score' => 'integer',
        'no_of_suggestion' => 'integer',
        'suggestion_score' => 'integer',
        'housekeeping' => 'integer',
        'housekeeping_score' => 'integer',
        'productivity' => 'integer',
        'productivity_score' => 'integer',
        'quality' => 'integer',
        'quality_score' => 'integer',
        'compliance' => 'integer',
        'compliance_score' => 'integer',
        'learning_score' => 'integer',
        'product_knowledge' => 'integer',
        'product_knowledge_score' => 'integer',
    ];

    public function student()
    {
        return $this->belongsTo(StudentAdmissionLegs::class);
    }

    public function master()
    {
        return $this->belongsTo(Pstevaluationmaster::class, 'master_id');
    }
}
