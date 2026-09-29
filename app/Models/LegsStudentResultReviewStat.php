<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LegsStudentResultReviewStat extends Model
{
    protected $table = 'legs_student_result_review_stats';

    /**
     * ✅ Fillable fields
     */
    protected $fillable = [
        'uuid',
        'batch_id',
        'semester_id',
        'exam_type',

        'is_reviewed',
        'reviewed_by',
        'reviewed_at',

        'is_validated',
        'validated_by',
        'validated_at',

        'is_approved',
        'approved_by',
        'approved_at',

        'is_locked',
        'issue_date',
        'is_issued',
        'issued_by',
    ];

    /**
     * ✅ Type casting
     */
    protected $casts = [
        'is_reviewed'  => 'boolean',
        'is_validated' => 'boolean',
        'is_approved'  => 'boolean',
        'is_locked'    => 'boolean',

        'reviewed_at'  => 'datetime',
        'validated_at' => 'datetime',
        'approved_at'  => 'datetime',
    ];

    /**
     * ✅ Auto-generate UUID
     */

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid7();
            }
        });
    }

    /**
     * 🔗 Relationships (optional but useful)
     */

    // public function batch()
    // {
    //     return $this->belongsTo(\App\Models\LegsStudentBatch::class, 'batch_id');
    // }

    // public function semester()
    // {
    //     return $this->belongsTo(\App\Models\LegsSemester::class, 'semester_id');
    // }

    public function reviewer()
    {
        return $this->belongsTo(\App\Models\User::class, 'reviewed_by');
    }

    public function validator()
    {
        return $this->belongsTo(\App\Models\User::class, 'validated_by');
    }

    public function approver()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }
}