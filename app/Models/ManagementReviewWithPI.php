<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManagementReviewWithPI extends Model
{
    use HasFactory;
    protected $fillable = [
        'industry_partner_id',
        'program_id',
        'legs_zone_id',
        'semester_id',
        'batch',
        'date_and_time',
        'meeting_place',
        'duration',
        'reason',
        'attended_by',
        'distribution_to',
        'outcome',
        'date_time_meeting',
        'to_be_held',
        'minutes_prepaired'

    ];

    public function details()
    {
        return $this->hasMany(ManagementReviewWithPIDetails::class , 'meeting_id' , 'id');
    }
}
