<?php

namespace App\Models\CommonModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class UserIdentification extends Model
{
    use HasFactory, Notifiable, LogsActivity;
    protected $fillable = [
        'user_id',
        'fullname',
        'date_of_birth',
        'aadhaar_number',
        'pan_number',
        'voter_id',
        'passport_number',
        'issued_country',
        'email',
        'phone_number',
        'address',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
