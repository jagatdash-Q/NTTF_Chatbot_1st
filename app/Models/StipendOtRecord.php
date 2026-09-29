<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StipendOtRecord extends Model
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
        'student_code',
        'qualification',
        'ot_hours',
        'month',
        'year',
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
        'ot_hour' => 'decimal:1',
        'month' => 'integer',
        'year' => 'integer',
        'legs_zones_id' => 'integer',
        'industry_partner_master_details_id' => 'integer',
        'billing_plant_id' => 'integer',
        'created_by' => 'integer',
        'updated_by' => 'integer',
    ];

    /**
     * Get the stipend OT master for this record.
     */
    public function stipendOtMaster()
    {
        return $this->belongsTo(StipendOtMaster::class, 'legs_student_batches_id', 'legs_student_batches_id')
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

    /**
     * Scope to filter by student code.
     */
    public function scopeByStudentCode($query, $studentCode)
    {
        return $query->where('student_code', $studentCode);
    }
}
