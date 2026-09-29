<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\UuidV7;

class CoreMidTermMarkEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'academic_month',
        'academic_year',
        'program_id',
        'program_name',
        'branch_id',
        'branch_name',
        'semester',
        'subject_code',
        'subject_name',
        'student_name',
        'student_token_id',
        'theory',
        'practical',
        'total',
        'grade',
        'remarks',
        'created_by',
        'updated_by'
    ];

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->uuid = new UuidV7();
        });
    }
} 