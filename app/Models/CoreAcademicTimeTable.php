<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Storage;

use App\Models\CommonModels\Centres;
use App\Models\AcademicSectionModels\CollegeAcademicProgrammeStreamBranches;
use App\Models\AcademicSectionModels\CollegeAcademicProgrammeStreams;
use App\Models\AcademicSectionModels\StreamBranchSemester;
use App\Models\AcademicSectionModels\Subjects;
use App\Models\CoreMidtermTimeTable;
use App\Models\User;
use App\Models\CoreBatch;
use App\Models\AcademicSectionModels\CollegeAcademicProgrammes;
use App\Models\AcademicSectionModels\Semesters;
use  \App\Models\CoreAdmissionCurrentAcademicYear;

class CoreAcademicTimeTable extends Model
{
    use HasFactory,  LogsActivity;
    protected $fillable = [
        'center_id',
        'center_name',
        'program_id',
        'program_name',
        'stream_id',
        'stream_name',
        'batch_id',
        'batch_name',
        'section_id',
        'section_name',
        'shift_id',
        'shift_name',
        'classroom_id',
        'classroom_name',
        'subject_id',
        'subject_name',
        'subject_short_name',
        'employee_id',
        'employee_short_name',
        'day',
        'session_no',
        'session_start_time',
        'session_end_time',
        'academic_year_id',
        'semester_no',
        'created_by',
        'updated_by',
        'academic_year_span',
        'session_name',
        'start_date',
        'end_date','group_id', 'group_name', 'extra_course',
    'is_extra_activity',

    ];
    public $table = 'core_academic_time_tables';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
    public function center() { return $this->belongsTo(Centres::class, 'center_id'); }
    public function program() { return $this->belongsTo(CollegeAcademicProgrammes::class, 'program_id'); }
    public function stream() { return $this->belongsTo(CollegeAcademicProgrammeStreams::class, 'stream_id'); }
    public function batch() { return $this->belongsTo(CoreBatch::class, 'batch_id'); }
    public function section() { return $this->belongsTo(CoreSection::class, 'section_id'); }
    public function shift() { return $this->belongsTo(ShiftingMaster::class, 'shift_id'); }
    public function classroom() { return $this->belongsTo(CoreCenterClassroom::class, 'classroom_id'); }
    public function subject() { return $this->belongsTo(Subjects::class, 'subject_id'); }
    public function teacher() { return $this->belongsTo(User::class, 'employee_id'); }
    
}
