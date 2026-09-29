<?php

namespace App\Models;

use App\Models\CommonModels\Centres;
use App\Models\CommonModels\LegsZone;
use App\Models\IndustryPartnerModels\IndustryPartnerMasterDetails;
use App\Models\LegsProgrammeModels\LegsCollegeAcademicProgrammeStreams;
use App\Models\LegsProgrammeModels\LegsSemesters;
use App\Models\LegsProgrammeModels\LegsStudentBatch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegsStudentSemesterEligibility extends Model
{
    use HasFactory;

    protected $table = 'legs_student_semester_eligibility';

    protected $fillable = [
        'uuid',
        'legs_zones_id',
        'industry_partner_master_details_id',
        'billing_plant_id',
        'legs_student_batches_id',
        'legs_college_academic_programme_streams_id',
        'legs_semesters_id',
        'exam_type',
        'semester_timetable_id',
        'center_id',
        'student_code',
        'is_eligible',
        'is_manually_eligible',
        'manually_eligible_by',
        'is_email_sent',
        'created_by',
        'updated_by',
        'theory_percentage',
        'attendance_percentage',
        'practical_percentage'
    ];
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (is_null($model->center_id)) {
                $model->center_id = 1;
            }
        });
    }
    // Relationships
    public function zone()
    {
        return $this->belongsTo(LegsZone::class, 'zone_id');
    }

    public function industry()
    {
        return $this->belongsTo(IndustryPartnerMasterDetails::class, 'industry_id');
    }

    public function program()
    {
        return $this->belongsTo(LegsCollegeAcademicProgrammeStreams::class, 'program_id');
    }

    public function batch()
    {
        return $this->belongsTo(LegsStudentBatch::class, 'batch_id');
    }

    public function semester()
    {
        return $this->belongsTo(LegsSemesters::class, 'semester_id');
    }

    public function center()
    {
        return $this->belongsTo(Centres::class, 'center_id');
    }

    // Scopes
    public function scopeEligible($query)
    {
        return $query->where(function ($q) {
            $q->where('is_eligible', true)
                ->orWhere('is_manually_eligible', true);
        });
    }

    public function scopeNotEligible($query)
    {
        return $query->where('is_eligible', false)
            ->where('is_manually_eligible', false);
    }
}
