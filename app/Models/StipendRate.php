<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StipendRate extends Model
{
    use HasFactory;

    protected $table = 'stipend_rates';

    protected $fillable = [
        'industry_partner_master_details_id',
        'legs_zones_id',
        'billing_plant_id',
        'batch_year',
        'qualification',
        'rate_per_month',
        'ot_rate_per_hr',
        'effective_from',
        'effective_to',
        'stipend_cycle_start_date',
        'stipend_cycle_end_date',
        'status',
        'created_by',
        'updated_by',
        'incentive_bonus',
        'attendance_bonus'
    ];

    /**
     * Get all the attendances for the stipend rate
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(StipendAttendance::class);
    }

    /**
     * Get all the incentives for the stipend rate
     */
    public function incentives(): HasMany
    {
        return $this->hasMany(StipendIncentive::class);
    }

    /**
     * Get all the deductions for the stipend rate
     */
    public function deductions(): HasMany
    {
        return $this->hasMany(StipendDeduction::class);
    }

    /**
     * Get all the arrears for the stipend rate
     */
    public function arrears(): HasMany
    {
        return $this->hasMany(StipendArrear::class);
    }

    /**
     * Get all the OT records for the stipend rate
     */
    public function otRecords(): HasMany
    {
        return $this->hasMany(OtRecord::class);
    }

    /**
     * Get all the calculations for the stipend rate
     */
    public function calculations(): HasMany
    {
        return $this->hasMany(StipendCalculation::class);
    }
}
