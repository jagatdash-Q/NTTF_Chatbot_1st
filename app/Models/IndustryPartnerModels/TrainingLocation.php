<?php

namespace App\Models\IndustryPartnerModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class TrainingLocation extends Model
{
    use HasFactory, LogsActivity;
    protected $table = 'training_locations'; // Specify the table name

    protected $fillable = [
        'industry_partner_id',
        'introduction',
        'weekly_theory_sessions',
        'on_the_job_training',
        'status',
        'created_by',
        'updated_by',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
       
    }
}
