<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\CoreProgrammeModels\StudentAdmission;
use App\Models\ConvocationCoreProcess;
use App\Models\CoreProgrammeModels\Studentlead;

class ConvocationCoreCorrection extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'convocation_core_corrections';
    protected $guarded = [];

    public function process()
    {
        return $this->belongsTo(ConvocationCoreProcess::class, 'convocation_process_id');
    }

    public function student()
    {
        return $this->belongsTo(\App\Models\CoreProgrammeModels\StudentAdmission::class, 'student_admission_id');
    }

    public function studentLead(){
        return $this->belongsTo(Studentlead::class, 'student_lead_id');
    }
}
