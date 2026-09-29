<?php

namespace App\Models\CommonModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class StudentFamily extends Model
{
    use HasFactory, Notifiable, LogsActivity;
    protected $fillable = [
        'student_lead_id',
        'family_details_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function getFamilyDetails(): HasOne
    {
        return $this->hasOne(FamilyDetails::class, 'id', 'family_details_id');
    }
}
