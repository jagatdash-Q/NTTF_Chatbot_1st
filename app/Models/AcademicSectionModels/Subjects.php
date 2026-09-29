<?php

namespace App\Models\AcademicSectionModels;

use App\Models\CoreAdmissionCurrentAcademicYear;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Subjects extends Model
{
    use HasFactory ,SoftDeletes,LogsActivity;
    protected $fillable = ['stream','branch','subject_name','subject_code','is_theory','is_practical','hour_per_semester','credit',
    'elective_group','semester','is_elective','is_ojt','academic_year','academic_month','program_type','core_admission_current_academic_year_id','year_month','sort_name'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function current_academic_year_details()
    {
        return $this->hasOne(CoreAdmissionCurrentAcademicYear::class, 'id', 'core_admission_current_academic_year_id');
    }
}
