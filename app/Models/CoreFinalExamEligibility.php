<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\UuidV7;

class CoreFinalExamEligibility extends Model
{
    use HasFactory;

    protected $table = 'core_final_exam_eligibilities';

    protected $fillable = [
        'uuid',
        "program_name",
        "program_id",
        "branch_name",
        "branch_id",
        "semester",
        'batch_month', // string, only "July" or "September"
        'batch_year',  // int, e.g., 2024
        'subject_code',
        'subject_name',
        'theory',
        'practical',
        'attendance',
        'created_by',
        'updated_by',
        'core_batch_id',
        'project_mark',
        'ojt_mark',
        'academic_year',
        'academic_month',
        'academic_year_span',
        'payment_percentage',
        'attendance_percentage',
        'theory_file_mark',
        'practical_file_mark',
        'final_mark_type'
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