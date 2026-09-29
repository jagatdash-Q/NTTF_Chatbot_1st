<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StipendDeduction extends Model
{
    use HasFactory;

    protected $table = 'stipend_deductions';
    
    protected $fillable = [
        'student_code',
        'industry_partner_master_details_id',
        'legs_zones_id',
        'billing_plant_id',
        'legs_student_batches_id',
        'month',
        'year',
        'canteen_deduction',
        'extra_canteen_deduction',
        'transport_deduction',
        'extra_uniform',
        'semester_exam',
        'study_material',
        'medical_deduction',
        'penalty_deduction',
        'other_deductions',
        'remarks',
        'status',
        'created_by',
        'updated_by',
        'deduction_amount_2_col_name',
        'deduction_amount_3_col_name',
        'deduction_amount_4_col_name',
        'deduction_amount_2',
        'deduction_amount_3',
        'deduction_amount_4',
    ];

    /**
     * Get the stipend rate that owns the deduction
     */
    public function stipendRate(): BelongsTo
    {
        return $this->belongsTo(StipendRate::class);
    }
}
