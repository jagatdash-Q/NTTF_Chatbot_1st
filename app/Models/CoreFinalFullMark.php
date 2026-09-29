<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\UuidV7;

class CoreFinalFullMark extends Model
{
    use HasFactory;

    protected $table = 'core_final_full_marks';

    protected $fillable = [
        'uuid',
        "program_name",
        "program_id",
        "branch_name",
        "branch_id",
        "semester",
        'batch_month', // string, only "July" or "September"
        'batch_year',  // int, e.g., 2024
        'subject_code', // string, subject code
        'subject_name', // string, subject name
        'exam_mark',   // numeric, exam mark
        'file_mark',   // numeric, file mark
        'pass_mark',   // numeric, pass mark
        'created_by',
        'updated_by',

        'core_batch_id'
        ,
        'academic_year',
        'academic_month',
        'academic_year_span'
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