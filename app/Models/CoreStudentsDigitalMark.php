<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\User;

class CoreStudentsDigitalMark extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = [
        'core_question_upload_id',
        'core_student_id',
        'core_token_id',
        'marks_obtained',
        'total_marks',
        'percentage',
        'document_url',
        'document_name',
        'remarks',
        'status',
        'created_by',
        'updated_by',
        'duration_minutes',
        'start_time',
        'end_time',
        'exam_type',
        'academic_year',
        'suppl_no',
        'exam_name',
        'test_type',
        'assigned_by',
        'assigned_by_name',
        'remarks',
        'stream_name',
        'stream_id',
        'program_name',
        'program_id',
        'subject_id',
        'subject_name',
        'semester_id',
        'core_zone_id',
        'center_id',
        'center_name',
        'zone_name',
        'is_evaluated',
        'is_answer_readable'
    ];
    protected $casts = [
        'question_paper_details' => 'array',
    ];
    public $table = 'core_students_digital_marks';
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
    public function creator()
    {
    return $this->belongsTo(User::class, 'created_by');
    }
}
