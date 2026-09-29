<?php

namespace App\Models;

use App\Models\CommonModels\LegsZone;
use App\Models\IndustryPartnerModels\IndustryPartnerMasterDetails;
use App\Models\LegsProgrammeModels\LegsCollegeAcademicProgrammeStreams;
use App\Models\LegsProgrammeModels\LegsSemesters;
use App\Models\LegsProgrammeModels\LegsStudentBatch;
use App\Models\LegsProgrammeModels\LegsSubject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;

class LegsStudentSemesterMarkEntry extends Model
{
    use HasFactory;

    protected $table = 'legs_student_semester_mark_entry';

    protected $fillable = [
        'uuid',
        'legs_zones_id',
        'industry_partner_master_details_id',
        'legs_student_batches_id',
        'billing_plant_id',

        'legs_college_academic_programme_streams_id',
        'legs_semesters_id',
        'exam_type',
        'lsep',
        'student_code',
        'student_name',
        'legs_subjects_subject_code',
        'mark_secured',
        'is_absent',
        'is_mp',
        'is_ne',
        'is_ml',
        'is_sp',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'mark_secured' => 'decimal:2',
        'is_absent' => 'boolean',
        'is_mp' => 'boolean',
        'is_ne' => 'boolean',
        'is_ml' => 'boolean',
        'is_sp' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->uuid = new UuidV7();
        });
    }

    /**
     * Get the zone associated with the mark entry.
     */
    public function legsZone(): BelongsTo
    {
        return $this->belongsTo(LegsZone::class, 'legs_zones_id');
    }

    /**
     * Get the industry partner associated with the mark entry.
     */
    public function industryPartner(): BelongsTo
    {
        return $this->belongsTo(IndustryPartnerMasterDetails::class, 'industry_partner_master_details_id');
    }

    /**
     * Get the student batch associated with the mark entry.
     */
    public function studentBatch(): BelongsTo
    {
        return $this->belongsTo(LegsStudentBatch::class, 'legs_student_batches_id');
    }

    /**
     * Get the academic programme stream associated with the mark entry.
     */
    public function academicProgrammeStream(): BelongsTo
    {
        return $this->belongsTo(LegsCollegeAcademicProgrammeStreams::class, 'legs_college_academic_programme_streams_id', 'id');
    }

    /**
     * Get the semester associated with the mark entry.
     */
    public function semester(): BelongsTo
    {
        return $this->belongsTo(LegsSemesters::class, 'legs_semesters_id');
    }

    /**
     * Get the subject associated with the mark entry.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(LegsSubject::class, 'legs_subjects_subject_code', 'subject_code');
    }

    /**
     * Get the user who created the record.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the record.
     */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
