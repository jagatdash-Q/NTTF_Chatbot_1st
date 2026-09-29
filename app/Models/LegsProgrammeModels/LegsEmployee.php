<?php

namespace App\Models\LegsProgrammeModels;

use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LegsEmployee extends Model
{
    protected $fillable = [
        'uuid',
        'user_id',
        'first_name',
        'last_name',
        'short_name',
        'employee_code',
        'email',
        'blood_group',
        'mobile',
        'work_location',
        'personal_email',
        'date_of_joining',
        'date_of_revoking',
        'pan_number',
        'passport_number',
        'passport_valid_up_to',
        'temporary_address',
        'permanent_address',
        'gender',
        'employment_type',
        'employee_type',
        'reports_to',
        'specialization',
        'university',
        'institute_name',
        'location',
        'from_date',
        'to_date',
        'percentage',
        'cgpa',
        'year_of_experience',
        'no_of_years_worked',
        'old_company_name',
        'designation',
        'old_company_from_date',
        'old_company_to_date',
        'status',
        'primary_center_ids',
        'mapped_center_ids',
        'batch_ids',
        'program_ids',
        'subject_ids',
        'roles',
        'role_ids',
        'zone_id',
        'industry_id',
        'signature_path',
        'is_trainer',
        'employee_status',
        'created_by',
        'modified_on',
        'modified_by',
        'industry_id',
        'zone_id',
        'billing_plant_id',
         'batch_id', 'program_id', 'subject_id', 'semester_id'
    ];
    protected $casts = [
        'role_ids' => 'array',
        'subject_ids' => 'array',
        'program_ids' => 'array',
        'batch_ids' => 'array',
        'mapped_center_ids' => 'array',
        'primary_center_ids' => 'array',
    ];

    public $table = 'legs_employees';
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();

    }
    public function employee()
    {
        return $this->belongsTo(User::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'user_skills')
            ->withTimestamps();
    }

    public function getSignatureAttribute()
    {
        return $this->signature_path ? asset('storage/' . $this->signature_path) : null;
    }
}

