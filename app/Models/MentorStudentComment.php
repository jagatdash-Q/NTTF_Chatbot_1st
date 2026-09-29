<?php

namespace App\Models;

use App\Models\CoreProgrammeModels\StudentAdmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MentorStudentComment extends Model
{
    use HasFactory;

    protected $table = 'mentor_student_comments';

    protected $fillable = [
        'mentor_id',
        'student_id',
        'comment_date',
        'student_conversation',
        'mentor_comments',
        'remarks',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'comment_date' => 'date',
    ];

    public function mentor()
    {
        return $this->belongsTo(User::class, 'mentor_id');
    }

    public function student()
    {
        return $this->belongsTo(StudentAdmission::class, 'student_id');
    }
}
