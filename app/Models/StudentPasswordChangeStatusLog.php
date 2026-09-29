<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CoreProgrammeModels\StudentAdmission;
use App\Models\User;

class StudentPasswordChangeStatusLog extends Model
{
    use HasFactory;

    protected $table = 'student_password_change_status_logs';

    protected $fillable = [
        'user_id',
        'updated_by',
        'old_password_status',
        'new_password_status',
    ];

    /**
     * Student whose password status was updated.
     */
    public function student()
    {
        return $this->belongsTo(StudentAdmission::class, 'student_id');
    }

    /**
     * Admin/User who performed the update.
     */
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}