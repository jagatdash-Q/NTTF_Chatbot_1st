<?php

namespace App\Models;

use App\Models\CoreProgrammeModels\StudentAdmission;
use App\Models\CoreProgrammeModels\Studentlead;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DemandStudentLogs extends Model
{
    protected $fillable = [
        'demand_id',
        'student_code',
        'student_lead_id',
        'is_email_sent',
        'is_demand_reverted',
        'semester_no',
        'demand_reverse_remarks',
        'demand_reverse_date'
    ];

    public function getStudentAdmissionDetails(): HasOne
    {
        return $this->hasOne(StudentAdmission::class, 'student_lead_id', 'student_lead_id');
    }

    public function getStudentLeadDetails(): HasOne
    {
        return $this->hasOne(Studentlead::class, 'id', 'student_lead_id');
    }
}
