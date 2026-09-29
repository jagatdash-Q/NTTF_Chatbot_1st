<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StipendOtMaster extends Model
{

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'legs_zones_id',
        'industry_partner_master_details_id',
        'billing_plant_id',
        'legs_student_batches_id',
        'batch_year',
        'qualification',
        'ot_rate_hourly',
        'month',
        'year',
        'days',
        'status',
        'created_by',
        'updated_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'ot_rate_hourly' => 'decimal:2',
        'month' => 'integer',
        'year' => 'integer',
        'days' => 'integer',
        'legs_zones_id' => 'integer',
        'industry_partner_master_details_id' => 'integer',
        'billing_plant_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    /**
     * Get the stipend OT records for this master.
     */
    public function stipendOtRecords()
    {
        return $this->hasMany(StipendOtRecord::class, 'legs_student_batches_id', 'legs_student_batches_id')
            ->where('month', $this->month)
            ->where('year', $this->year);
    }

    /**
     * Scope to filter by active status.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to filter by inactive status.
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    /**
     * Scope to filter by month and year.
     */
    public function scopeByMonthYear($query, $month, $year)
    {
        return $query->where('month', $month)->where('year', $year);
    }
}
