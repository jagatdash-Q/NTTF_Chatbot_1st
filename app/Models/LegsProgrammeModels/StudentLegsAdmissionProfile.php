<?php


namespace App\Models\LegsProgrammeModels;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class StudentLegsAdmissionProfile extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = [
        'document_name',
        'student_id',
        'status',
        'document_types',
        'created_by',
        'updated_by'
    ];
    public $table = 'student_legs_admission_profiles';
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
