<?php

namespace App\Models\CoreProgrammeModels;

use App\Models\AcademicSectionModels\CollegeAcademicProgrammeStreams;
use App\Models\CommonModels\Centres;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class StudentCentreCoursePreferences extends Model
{
    use HasFactory, Notifiable, LogsActivity;
    protected $fillable = [
        'student_lead_id',
        'college_academic_programme_streams_id',
        'preference_1',
        'preference_2',
        'remarks',
        'status',
        'created_by',
        'updated_by',
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
    public function getStream(): HasOne
    {
        return $this->hasOne(CollegeAcademicProgrammeStreams::class, 'id', 'college_academic_programme_streams_id');
    }
    public function getPreference1():HasOne
    {
        return $this->hasOne(Centres::class, 'id', 'preference_1');
    }
    public function getPreference2():HasOne
    {
        return $this->hasOne(Centres::class, 'id', 'preference_1');
    }
}
