<?php

namespace App\Models;

use App\Models\Skill;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Employee extends Model
{
    protected $fillable = [
        'first_name',
        'user_id',
        'uuid',
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
        'primary_center',
        'mapped_centers',
        'batches',
        'courses',
        'roles',
        'core_zone_id',
        'program_types',
        'centre_ids',
        'stream_types',
        'subject_ids',
        'semester_ids',
        'is_trainer','signature_path',
        'date_of_birth',     
        'qualification_id',  
        'experience_id',
        'aadhar_number',
        'industry_id',
        'legs_zone_id',
        'billing_plant_id',
        'leave_approve_from',
        'nttf_odoo_employee_id',
        'emp_short_name','is_core_employee','employee_user_status'
    ];
    // protected $casts = [
    //     'primary_center' => 'array',
    //     'mapped_centers' => 'array',
    //     'batches' => 'array',
    //     'courses' => 'array',
    //     'roles' => 'array',
    // ];

    public $table = 'employees';
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();

    }
    public function employee()
    {
        return $this->belongsTo(User::class)->with(['getCenter']);
    }

    public function getEmployeeDetails()
    {
        return $this->hasOne(User::class,'id','user_id')->with(['getCenter']);
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
