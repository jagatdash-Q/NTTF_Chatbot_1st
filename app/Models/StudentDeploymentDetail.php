<?php

namespace App\Models;

use App\Models\DeploymentAreaMaster;
use App\Models\LegsProgrammeModels\StudentAdmissionLegs;
use Illuminate\Database\Eloquent\Model;
use App\Models\LegsProgrammeModels\LegsStudentBatch;

class StudentDeploymentDetail extends Model
{
    protected $fillable = [
        'deployment_master_id',
        'student_id',
        'department_name',
        'area_name',
        'start_date',
        'end_date',
        'status',
        'batch_id',
        'legs_zone_id',
        'billing_plant_id',
        'industry_partner_id',
    ];

    public function deploymentMaster()
    {
        return $this->belongsTo(DeploymentAreaMaster::class, 'deployment_master_id');
    }

    public function student()
    {
        return $this->belongsTo(StudentAdmissionLegs::class, 'student_id');
    }
    public function batch()
    {
        return $this->belongsTo(LegsStudentBatch::class, 'batch_id');
    }
}
