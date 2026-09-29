<?php

namespace App\Models\CommonModels;

use App\Models\AcademicSectionModels\CollegeAcademicProgrammes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class HighestEducationalQualification extends Model
{
    use HasFactory, Notifiable, SoftDeletes, LogsActivity;
    public $table = "highest_educational_qualifications";
    protected $fillable = [
        'uuid',
        'program_type',
        'highest_degree',
        'field_of_study',
        'description',
        'created_by',
        'updated_by','lateral_entry'
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
    public function getProgramType(): HasOne
    {
        return $this->hasOne(CollegeAcademicProgrammes::class, 'id', 'program_type');
    }
}
