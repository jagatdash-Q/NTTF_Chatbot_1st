<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StipendArrear extends Model
{
    use HasFactory;

    protected $table = 'stipend_arrears';

    protected $fillable = [
        'student_code',
        'industry_partner_master_details_id',
        'legs_zones_id',
        'billing_plant_id',
        'stipend_attendance_id',
        'legs_student_batches_id',
        'month',
        'year',
        'arrear_amount',
        'days',
        'reason',
        'status',
        'created_by',
        'updated_by'
    ];

    /**
     * Get the stipend rate that owns the arrear
     */
    public function stipendRate(): BelongsTo
    {
        return $this->belongsTo(StipendRate::class);
    }

    /**
     * Get the attendance that owns the arrear
     */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(StipendAttendance::class, 'stipend_attendance_id');
    }
}
