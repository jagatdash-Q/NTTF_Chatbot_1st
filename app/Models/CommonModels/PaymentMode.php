<?php

namespace App\Models\CommonModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PaymentMode extends Model
{
    use HasFactory, Notifiable, SoftDeletes, LogsActivity;
    public $table = "payment_modes";
    protected $fillable = [
        'uuid',
        'payment_mode_name',
        'payment_mode_description',
        'payment_mode_charge',
        'is_active',
        'remarks',
        'status',
        'created_by',
        'updated_by',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
