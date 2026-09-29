<?php


namespace App\Models\LegsProgrammeModels;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class LegsBatchSyllabus extends Model
{
    use HasFactory, LogsActivity;
   
   
    protected $fillable = [
        'program_type',
        'program_type_id',
        'uuid',
        'program_name',
        'program_stream_id',
        'issued_by_department_name',
        'issued_person_name',
        'semester_id',
        'semester_name',
        'subject_id',
        'subject_name',
        'subject_code',
        'is_theory',
        'is_elective',
        'is_ojt',
        'is_lab',
        'hours_per_semester',
        'credit',
        'revision_number',
        'revision_date',
        'subject_details_content',
        'topics_details',
        'detailed_syllabus',
        'reference_books',
        'learning_websites',
        'status',
        'created_by',
        'updated_by',
        'syllabus_code',
        'industry_partner','syllabus_data','batch_id'
    ];
    public $table = 'legs_batch_syllabi';
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
