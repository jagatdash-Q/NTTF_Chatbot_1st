<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeploymentMaster extends Model
{
    protected $fillable = ['zone_id','industry_partner_id','department','deployment_area','billing_plant_id','batch_id'];
}
