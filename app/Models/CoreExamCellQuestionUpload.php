<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;

class CoreExamCellQuestionUpload extends Model
{
    use HasFactory;

   protected $fillable = [
        'document_name',
        'core_exam_cell_mem_id',
        'status',
        'exam_date',
        'start_time',
        'end_time',
        'exam_type',
        'exam_name',
        'exam_code',
        'exam_duration',
        'core_exam_cell_member',
        'core_exam_cell_member_email',
        'core_exam_cell_member_plant_head',
        'core_exam_cell_member_plant_head_email',
        'core_exam_cell_member_plant_head_mobile',
        'core_document_types',
        'subject_id',
        'program_id',
        'stream_id',
        'center_id',
        'semester_id',
        'core_zone_id',
        'subject_name',
        'program_name',
        'stream_name',
        'center_name',
        'zone_name',
        'created_by',
        'updated_by',
        'exam_type_for_semester',
        'exam_download_gap_hours',
        'exam_time',
        'academic_year_span',
        'suppl_no'
    ];

    
    public $table = 'core_exam_cell_question_uploads';
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    
}
