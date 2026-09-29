<?php

namespace App\Models;

use App\Models\CoreProgrammeModels\StudentAdmission;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MentoringRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'mentoring_records';

    protected $fillable = [
        'mentor_user_id',
        'student_id',
        'student_code',
        'batch_id',
        'batch_name',
        'section_id',
        'section_name',
        'enrolement_status',
        'mentoring_status',
        'note_date',
        'conversation_details',
        'mentor_comments',
        'suspension_start_date',
        'suspension_end_date',
        'suspension_days',
        'created_by',
        'updated_by',
        'meta',
        'reported_by_name',
        'violation_type',
        'violation',
        'card',
        'action_taken',
        'investigation_status',
        'investigation_closed_date',
        'approved_by',
        'staff_comments',
        'violation_place',
        'supporting_evidence',
        'evidence_file',
        'evidence_original_name',
        'student_conversation',
        'disposition',
    ];

    protected $casts = [
        'note_date' => 'date',
        'suspension_start_date' => 'date',
        'suspension_end_date' => 'date',
        'meta' => 'array',
    ];

    /**
     * Mentor (user)
     */
    public function mentor()
    {
        return $this->belongsTo(\App\Models\User::class, 'mentor_user_id');
    }

    /**
     * Student
     * - adjust Student::class namespace if different in your app
     */
    public function student()
    {
        return $this->belongsTo(StudentAdmission::class, 'student_id');
    }

    /**
     * Scopes
     */
    public function scopeForMentor($query, $mentorUserId)
    {
        return $query->where('mentor_user_id', $mentorUserId);
    }

    public function scopeForStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    /**
     * Convenience to check active suspension
     */
    public function isSuspended(): bool
    {
        if ($this->suspension_end_date === null) {
            return false;
        }

        return $this->suspension_end_date->isFuture();
    }
}
