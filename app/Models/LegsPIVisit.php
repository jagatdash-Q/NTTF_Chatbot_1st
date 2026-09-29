<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegsPIVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        'industry_partner_id',
        'program_id',
        'legs_zone_id',
        'semester_id',
        'batch',
        'billing_plant_id',
        'billing_plant_name',
    ];

    public function details()
    {
        return $this->hasMany(LegsPIVisitDetails::class , 'visit_id' , 'id');
    }
}
