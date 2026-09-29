<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\UuidV7;

class CoreSemesterTimeTable extends Model
{
    use HasFactory;

    protected $table = 'core_semester_time_tables';

    protected $fillable = [
        'uuid',
        'program_name',
        'program_id',
        'branch_name',
        'branch_id',
        'semester',
        'batch_month',
        'batch_year',
        'subject_code',
        'subject_name',
        'date',
        'start_time',
        'end_time',
        'examiner_id',
        'examiner_name',
        'evaluator_id',
        'evaluator_name',
        'subject_incharge_id',
        'subject_incharge_name',
        'syllabus_completed',
        'created_by',
        'updated_by',
        'exam_type',
        'suppl_no',
        'exam_name',
        'test_type',
        'stream_id',
        'stream_name',
        'stream_types',

        'core_batch_id',
        'core_batch_name',
        'evaluation_type',
        'academic_year_span',
        'evaluation_date',
        'academic_year',
        'academic_month',
        'center_id',
        'center_name',
        'core_zone_id', 
        'subject_id',
        'status'
        
    ];
    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->uuid = new UuidV7();
        });
    }
}