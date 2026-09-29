<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtRecord extends Model
{
    use HasFactory;

    protected $table = 'ot_record';
    
    protected $fillable = [
        'student_code',
        'batch_id',
        'stipend_rate_id',
        'month',
        'year',
        'ot_hours',
        'status',
        'created_by',
        'updated_by'
    ];

    /**
     * Get the stipend rate that owns the OT record
     */
    public function stipendRate(): BelongsTo
    {
        return $this->belongsTo(StipendRate::class);
    }
}
