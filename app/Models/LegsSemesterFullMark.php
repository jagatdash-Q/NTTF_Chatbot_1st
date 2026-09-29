<?php


namespace App\Models;

use App\Models\CommonModels\LegsZone;
use App\Models\IndustryPartnerModels\IndustryPartnerMasterDetails;
use App\Models\LegsProgrammeModels\LegsCollegeAcademicProgrammeStreams;
use App\Models\LegsProgrammeModels\LegsSemesters;
use App\Models\LegsProgrammeModels\LegsStudentBatch;
use App\Models\LegsProgrammeModels\LegsSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Uid\UuidV7;

class LegsSemesterFullMark extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'legs_semester_full_marks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uuid',
        'lstt_uuid',
        'legs_zones_id',
        'billing_plant_id',
        'industry_partner_master_details_id',
        'legs_student_batches_id',
        'legs_college_academic_programme_streams_id',
        'legs_semesters_id',
        'legs_subjects_subject_code',
        'full_mark',
        'pass_mark',
        'status',
        'created_by',
        'updated_by',
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
     * Get the zone associated with the marks.
     */
    public function zone()
    {
        return $this->belongsTo(LegsZone::class, 'legs_zones_id');
    }

    /**
     * Get the industry partner associated with the marks.
     */
    public function industryPartner()
    {
        return $this->belongsTo(IndustryPartnerMasterDetails::class, 'industry_partner_master_details_id');
    }

    /**
     * Get the student batch associated with the marks.
     */
    public function studentBatch()
    {
        return $this->belongsTo(LegsStudentBatch::class, 'legs_student_batches_id');
    }

    /**
     * Get the academic programme stream associated with the marks.
     */
    public function academicProgrammeStream()
    {
        return $this->belongsTo(LegsCollegeAcademicProgrammeStreams::class, 'legs_college_academic_programme_streams_id');
    }

    /**
     * Get the semester associated with the marks.
     */
    public function semester()
    {
        return $this->belongsTo(LegsSemesters::class, 'legs_semesters_id');
    }

    /**
     * Get the subject associated with the marks.
     */
    public function subject()
    {
        return $this->belongsTo(LegsSubject::class, 'legs_subjects_subject_code', 'subject_code');
    }
}
