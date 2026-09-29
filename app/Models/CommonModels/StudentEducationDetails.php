<?php

namespace App\Models\CommonModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class StudentEducationDetails extends Model
{
    use HasFactory, Notifiable, LogsActivity;
    protected $fillable = [
        'student_lead_id',
        'medium',
        'course_name',
        'institution_name',
        'board_or_university',
        'year_of_completion',
        'percentage_or_cgpa',
        'medium_of_instruction',
        'domicile_category',
        'certification_or_document_path',
        'education_full_address',
        'extra_attempt',
        'class',
        'university',
        'remarks',
        'status',
        'created_by',
        'updated_by',
        'school_name',
        'place',
        'state',
        'district',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
