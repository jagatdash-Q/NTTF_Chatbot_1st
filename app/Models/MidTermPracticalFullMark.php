<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\UuidV7;

class MidTermPracticalFullMark extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'mid_term_practical_full_marks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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
        'exam_mark',   // numeric, practical mark
        'file_mark',   // numeric, practical mark
        'pass_mark',   // numeric, pass mark
        'created_by',
        'updated_by',
        'core_batch_id'
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