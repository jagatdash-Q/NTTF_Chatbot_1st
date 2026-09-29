<?php

namespace App\Models\CommonModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class FamilyDetails extends Model
{
    use HasFactory, Notifiable, LogsActivity;
    protected $fillable = [
        'user_id',
        'user_type',
        'relationship',
        'relationship_status',
        'name',
        'dob',
        'age',
        'gender',
        'contact',
        'email',
        'address',
        'occupation',
        'category',
        'health',
        'emergency_contact',
        'created_by',
        'modified_by',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
