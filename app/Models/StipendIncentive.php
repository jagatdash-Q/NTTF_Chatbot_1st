<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StipendIncentive extends Model
{
    use HasFactory;

    protected $table = 'stipend_incentives';

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
        'incentive_amount',
        'incentive_amount_2_col_name',
        'incentive_amount_3_col_name',
        'incentive_amount_4_col_name',
        'incentive_amount_2',
        'incentive_amount_3',
        'incentive_amount_4',
        'remarks',
        'status',
        'created_by',
        'updated_by'
    ];

    /**
     * Get the stipend rate that owns the incentive
     */
    public function stipendRate(): BelongsTo
    {
        return $this->belongsTo(StipendRate::class);
    }
}
