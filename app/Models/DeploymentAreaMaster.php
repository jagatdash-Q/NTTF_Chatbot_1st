<?php

namespace App\Models;

use App\Models\StudentDeploymentDetail;
use Illuminate\Database\Eloquent\Model;

class DeploymentAreaMaster extends Model
{
    protected $fillable = [
        'legs_zone_id',
        'industry_partner_id',
        'batch_id',
        'billing_plant_id',
        'created_by',
        'updated_by',
        'status',
    ];

    public function studentDeployments()
    {
        return $this->hasMany(StudentDeploymentDetail::class, 'deployment_master_id');
    }
}
