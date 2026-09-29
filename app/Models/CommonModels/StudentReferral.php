<?php

namespace App\Models\CommonModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;


class StudentReferral extends Model
{
    use HasFactory, Notifiable, LogsActivity;
    public $fillable = [
        'student_lead_id',
        'code',
        'name',
        'centre',
        'course',
        'phone_number',
        'roll_number',
        'address',
        'agency_name',
        'year_of_passing',
        'program_name',
        'status'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
