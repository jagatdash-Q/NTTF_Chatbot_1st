<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LegsSessionalFullMark extends Model
{
    use HasFactory;

    protected $table = 'legs_sessional_full_marks';

    protected $fillable = [
        'uuid',
        'lstt_uuid',
        'legs_zones_id',
        'industry_partner_master_details_id',
        'legs_student_batches_id',
        'legs_college_academic_programme_streams_id',
        'legs_semesters_id',
        'legs_subjects_subject_code',
        'full_mark',
        'pass_mark',
        'is_old_record',
        'status',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_old_record' => 'boolean',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted()
    {
        // Generate UUID if not provided
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the semester full marks for this sessional full mark.
     */
    public function semesterFullMark()
    {
        return $this->belongsTo(
            LegsSemesterFullMark::class,
            ['legs_subjects_subject_code', 'legs_semesters_id', 'legs_student_batches_id'],
            ['legs_subjects_subject_code', 'legs_semesters_id', 'legs_student_batches_id']
        );
    }
}
