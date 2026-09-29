<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\UuidV7;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CoreMidtermEligibility extends Model
{
    use HasFactory;

    protected $table = 'core_midterm_eligibilities';

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
        'core_batch_name',
        'project_mark',
        'ojt_mark',
        'design_mark',
        'academic_year',
        'academic_month',
        'academic_year_span',
        'attendance_percentage',
        'payment_percentage',
        'academic_year_span',
        'practical_file_mark',
        'theory_file_mark'
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
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}