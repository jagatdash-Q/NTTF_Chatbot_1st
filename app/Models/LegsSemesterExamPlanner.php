<?php

namespace App\Models;

use App\Models\LegsProgrammeModels\LegsSubject;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Ramsey\Uuid\Rfc4122\UuidV4;
use App\Models\CommonModels\LegsZone;
use App\Models\CommonModels\Zones;
use App\Models\IndustryPartnerModels\IndustryPartnerMasterDetails;
use App\Models\LegsProgrammeModels\LegsCollegeAcademicProgrammeStreams;
use App\Models\LegsProgrammeModels\LegsSemesters;
use App\Models\LegsProgrammeModels\LegsStudentBatch;
use App\Trait\HasDefaultRelations;
use Symfony\Component\Uid\UuidV7;

class LegsSemesterExamPlanner extends Model
{
    use HasDefaultRelations;
    protected $fillable = [
        'uuid',
        'legs_zones_id',
        'industry_partner_master_details_id',
        'legs_student_batches_id',
        'billing_plant_id',
        'legs_batch_date_yr',
        'legs_college_academic_programme_streams_id',
        'legs_semesters_id',
        'no_of_student_attending_exam',
        'mode_of_exam',
        'prev_sem_exam_conducted_on',
        'prev_suplm_exam_conducted_on',
        'status',
        'legs_subjects_subject_code',
        'subject_name',
        'date',
        'start_time',
        'end_time',
        'invigilator_id',
        'subject_in_charge_id',
        'evaluator_id',
        'invigilator_name',
        'evaluator_name',
        'subject_incharge_name',
        'syllabus_completed',
        'batch_year',
        'batch_month',
        'exam_name'
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'batch_year' => 'integer',
    ];
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
