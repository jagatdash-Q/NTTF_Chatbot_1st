<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoreResultPublishStatus extends Model
{
    use HasFactory;

    protected $table = 'core_result_publish_statuses';

    protected $fillable = [
        'program_id',
        'stream_id',
        'semester_id',
        'academic_year',
        'centre_id',
        'exam_type',
        'suppl_no',

        'is_publish_to_staff',
        'staff_published_at',
        'staff_published_by',

        'is_publish_to_student',
        'student_published_at',
        'student_published_by',

        'is_locked',
        'locked_at',
        'locked_by',
        'locked_by_name',
    ];

    protected $casts = [
        'is_publish_to_staff' => 'boolean',
        'is_publish_to_student' => 'boolean',
        'is_locked' => 'boolean',

        'staff_published_at' => 'datetime',
        'student_published_at' => 'datetime',
        'locked_at' => 'datetime',
    ];
}
