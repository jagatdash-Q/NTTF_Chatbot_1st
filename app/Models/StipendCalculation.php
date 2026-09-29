<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StipendCalculation extends Model
{
    use HasFactory;

    protected $table = 'stipend_calculations';
    
    protected $fillable = [
        'student_code',
        'industry_partner_master_details_id',
        'legs_zones_id',
        'billing_plant_id',
        'legs_student_batches_id',
        'month',
        'year',
        'base_stipend',
        'total_incentives',
        'performance_incentives',
        'performance_incentives_2',
        'performance_incentives_3',
        'performance_incentives_4',
        'total_deductions',
        'arrear_amount',
        'net_stipend',
        'calculation_date',
        'status',
        'created_by',
        'updated_by'
    ];

    /**
     * Get the stipend rate that owns the calculation
     */
    public function stipendRate(): BelongsTo
    {
        return $this->belongsTo(StipendRate::class);
    }
}