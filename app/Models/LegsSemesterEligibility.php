<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Uid\UuidV7;
use App\Models\CommonModels\LegsZone;
use App\Models\CommonModels\Zones;
use App\Models\IndustryPartnerModels\IndustryPartnerMasterDetails;
use App\Models\LegsProgrammeModels\LegsCollegeAcademicProgrammeStreams;
use App\Models\LegsProgrammeModels\LegsSemesters;
use App\Models\LegsProgrammeModels\LegsStudentBatch;
use App\Models\LegsProgrammeModels\LegsSubject;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegsSemesterEligibility extends Model
{
    use HasFactory;
    protected $fillable = [
        'legs_zones_id',
        'industry_partner_master_details_id',
        'billing_plant_id',
        'legs_subjects_subject_code',
        'legs_student_batches_id',
        'legs_batch_date_yr',
        'legs_college_academic_programme_streams_id',
        'legs_semesters_id',
        'sessional_theory_marks',
        'attendance',
        'practical_marks',
        'status',
    ];
    /**
     *  Setup model event hooks
     */
    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->uuid = new UuidV7();
        });
    }
    /**
     * Get the legs zone associated with the time table.
     */
    public function legsZone(): BelongsTo
    {
        return $this->belongsTo(LegsZone::class, 'legs_zones_id');
    }

    /**
     * Get the industry partner associated with the time table.
     */
    public function industryPartner(): BelongsTo
    {
        return $this->belongsTo(IndustryPartnerMasterDetails::class, 'industry_partner_master_details_id');
    }

    /**
     * Get the student batch associated with the time table.
     */
    public function studentBatch(): BelongsTo
    {
        return $this->belongsTo(LegsStudentBatch::class, 'legs_student_batches_id');
    }

    /**
     * Get the academic programme stream associated with the time table.
     */
    public function academicProgrammeStream(): BelongsTo
    {
        return $this->belongsTo(LegsCollegeAcademicProgrammeStreams::class, 'legs_college_academic_programme_streams_id', 'id');
    }


    /**
     * Get the semester associated with the time table.
     */
    public function semester(): BelongsTo
    {
        return $this->belongsTo(LegsSemesters::class, 'legs_semesters_id');
    }

    /**
     * Get the subject associated with the sessional timetable.
     *
     * @return BelongsTo
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(LegsSubject::class, 'legs_subjects_subject_code', 'subject_code');
    }
}
