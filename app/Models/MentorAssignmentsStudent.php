<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MentorAssignmentsStudent extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mentor_assignments_student';

    protected $fillable = [
        // mentor
        'mentor_user_id',

        // student
        'student_id',
        'student_lead_id',
        'student_code',

        // center
        'center_id',
        'center_name',

        // program
        'program_id',
        'program_name',

        // stream
        'stream_id',
        'stream_name',

        // academic year
        'academic_year',

        // batch
        'batch_id',
        'batch_name',

        // mentor assignment status
        'is_current_mentor',
        'assigned_at',

        // audit
        'assigned_by',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_current_mentor' => 'boolean',
        'assigned_at' => 'datetime',
    ];
}