<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Models\CoreProgrammeModels\StudentAdmission;

class ConvocationCoreProcess extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'convocation_core_processes';
    protected $guarded = [];

    /**
     * Cast date/time attributes to Carbon instances
     */
    protected $casts = [
        'lock_date' => 'datetime',
        'verification_email_sent_at' => 'datetime',
        'guest_email_sent_at' => 'datetime',
        'initiated_at' => 'datetime',
        'locked_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function corrections()
    {
        return $this->hasMany(ConvocationCoreCorrection::class, 'convocation_process_id');
    }

    public function confirmations()
    {
        return $this->hasMany(ConvocationCoreDobConfirmation::class, 'convocation_process_id');
    }

    public function guestSelections()
    {
        return $this->hasMany(ConvocationCoreGuestSelection::class, 'convocation_process_id');
    }

   public function batch()
{
    return $this->belongsTo(StudentAdmission::class, 'batch_name', 'batch_name');
}
}
