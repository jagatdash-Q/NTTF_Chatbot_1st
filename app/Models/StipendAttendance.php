<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StipendAttendance extends Model
{
    use HasFactory;

    protected $table = 'stipend_attendance';

    protected $fillable = [
        'student_code',
        'industry_partner_master_details_id',
        'legs_zones_id',
        'billing_plant_id',
        'legs_student_batches_id',
        'month',
        'year',
        'total_days_in_billing_cycle',
        'present_days',
        'lop_days',
        'non_working_days',
        'status',
        'created_by',
        'updated_by'
    ];

    /**
     * Get the stipend rate that owns the attendance
     */
    public function stipendRate(): BelongsTo
    {
        return $this->belongsTo(StipendRate::class);
    }

    /**
     * Get all the arrears for the attendance
     */
    public function arrears(): HasMany
    {
        return $this->hasMany(StipendArrear::class);
    }
}
