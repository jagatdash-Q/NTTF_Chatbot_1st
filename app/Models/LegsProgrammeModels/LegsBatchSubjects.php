<?php

namespace App\Models\LegsProgrammeModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LegsBatchSubjects extends Model
{
    use HasFactory,  LogsActivity;
    protected $fillable = ['stream', 'branch', 'subject_name', 'subject_code', 'is_theory', 'is_practical', 'hour_per_semester', 'credit', 'elective_group', 
    'semester', 'is_elective', 'is_ojt', 'industry_partner','code','batch_id','batch_name','billing_plant_name','billing_plant_id'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();

    }
}
