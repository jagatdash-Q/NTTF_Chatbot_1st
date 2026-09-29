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

class LeadMaster extends Model
{
    use HasFactory, Notifiable, SoftDeletes, LogsActivity;
    protected $table = 'lead_master';
    protected $fillable = [
        'uuid',
        'lead_name',
        'remarks',
        'is_active',
        'status',
        'created_by',
        'updated_by',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
