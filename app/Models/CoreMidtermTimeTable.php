<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\UuidV7;

class CoreMidtermTimeTable extends Model
{
    use HasFactory;

    protected $table = 'core_midterm_time_tables';

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
        'syllabus_completed',
        'created_by',
        'updated_by',
        'exam_type',
        'exam_name',
        'test_type',
        'stream_id',
        'stream_name',
        'stream_types',
        'core_batch_id',
        'core_batch_name',
        'evaluation_type',
       
        'evaluation_date',
        'academic_month',
        'academic_year',
        'academic_year_span',
        'zone_name',
        'core_zone_id',
        'center_id',
        'center_name',
        'subject_id',
        'status'
    ];

        /**
     *  Setup model event hooks
     */
    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->uuid = new UuidV7();
        });
    }
}