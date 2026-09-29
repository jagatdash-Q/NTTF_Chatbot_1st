<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StipendAttendanceBonus extends Model
{
    use HasFactory;


    protected $fillable = [
        'student_code',
        'industry_partner_master_details_id',
        'legs_zones_id',
        'batch_year',
        'student_code',
        'qualification',
        'billing_plant_id',
        'legs_student_batches_id',
        'month',
        'year',
        'status',
        'created_by',
        'updated_by'
    ];
}
