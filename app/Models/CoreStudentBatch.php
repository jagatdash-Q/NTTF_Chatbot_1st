<?php

namespace App\Models;

use App\Models\CoreProgrammeModels\StudentAdmission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoreStudentBatch extends Model
{
     protected $fillable = [
        'student_lead_id',
        'student_id',
        'batch_id',
        'section_id',
        'group_id',
        'zone_id',
        'center_id',
        'program_id',
        'stream_id',
        'assigned_by',
    ];

    public function StudentDetails(): BelongsTo
    {
        return $this->belongsTo(StudentAdmission::class);
    }
}
