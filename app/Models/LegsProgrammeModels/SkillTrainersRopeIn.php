<?php

namespace App\Models\LegsProgrammeModels;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class SkillTrainersRopeIn extends Model
{
    use HasFactory, Notifiable, LogsActivity;
    public $table = 'skill_trainers_rope_ins';
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'base_loaction',
        'qualification',
        'experience',
        'softskill_training',
        'status',
        'uuid',
        'created_by',
        'updated_by',
        'batch_id',
        'softskill_training_experience',
        'industry_partner_id',
        'legs_zone_id','user_id',
        'billing_plant_id',
        'program_id',
        'subject_id',
        'semester_id',

    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

}
