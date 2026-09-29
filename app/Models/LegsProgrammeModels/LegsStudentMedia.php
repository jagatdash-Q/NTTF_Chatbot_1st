<?php

namespace App\Models\LegsProgrammeModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class LegsStudentMedia extends Model
{
    use HasFactory,  LogsActivity;
    protected $fillable = [
       'media_manager_id',
       'student_admission_legs_id',
       'legs_student_required_documents_id'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
