<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Storage;

class Otpgenerate extends Model
{
    use HasFactory,  LogsActivity;
    protected $fillable = [
       'mobile_no',
        'otp',
        'validation_status',
        'created_by',
        'updated_by',


    ];
    public $table = 'otp_generation';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
    
}
