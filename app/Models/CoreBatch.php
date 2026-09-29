<?php

namespace App\Models;

use App\Models\AcademicSectionModels\CollegeAcademicProgrammes;
use App\Models\AcademicSectionModels\CollegeAcademicProgrammeStreams;
use App\Models\CommonModels\Centres;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CoreBatch extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = [
        'center_id',
        'batch_name',
        'batch_code',
        'batch_start_date',
        'batch_end_date',
        'academic_year',
        'academic_month',
        'created_by',
        'updated_by',
        'core_zone_id',
        'lms',
        'qualification',
        'number_of_students',
        'semester_no',
        'current_sem_no',
        'core_batch_durations',
        'program_id',
        'stream_id',
        'academic_year_id',
        'year_month'
    ];
    public $table = 'core_batches';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function getCenterDetails(): HasOne
    {
        return $this->hasOne(Centres::class, 'id', 'center_id');
    }

    public function getProgramDetails(): HasOne
    {
        return $this->hasOne(CollegeAcademicProgrammes::class, 'id', 'program_id');
    }

    public function getStreamDetails(): HasOne
    {
        return $this->hasOne(CollegeAcademicProgrammeStreams::class, 'id', 'stream_id');
    }

    public function getCurrentAcademicDetails(): HasOne
    {
        return $this->hasOne(CoreAdmissionCurrentAcademicYear::class, 'id', 'academic_year_id');
    }

}
