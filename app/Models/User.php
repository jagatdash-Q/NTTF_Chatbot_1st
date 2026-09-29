<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\CommonModels\Centres;
use App\Notifications\CustomPasswordResetNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasRoles, Notifiable,SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'center_id',
        'additional_center',
        'role_id',
        'user_type',
        'instructor_type',
        'is_tml',
        'billing_plant_id',
        'is_plant_head',

        'is_core_or_legs_user',
        'is_center_head',
        'is_core_student_or_legs',
        'industry_id',
        'legs_zone_id',
        'ip_address',
        'full_address',
        'latitude',
        'longitude',
        'login_time',
        'core_center_branch_name',
        'core_center_branch_id',
        'company_category_type_id',
        'company_programs_id',
        'company_division_name',
        'company_branch_name',
        'check_in_or_checkout',
        'user_status',
        'modified_date',
        'work_status',
        'can_take_attendance',
        'nttf_odoo_company_id',
        'old_center_id',
        'old_company_branch_name',
        'is_warden', 'doj', 'student_lead_id',
        'must_change_password',
        'password_changed_at',
    ];

    protected $with = ['roles'];

    protected $casts = [
        'role_id' => 'array',
        // 'additional_center' => 'array',
        'must_change_password' => 'boolean',
        'password_changed_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // public function roles()
    // {
    //     return $this->belongsToMany(Role::class, 'users_roles');
    // }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new CustomPasswordResetNotification($token));
    }

    public function getCenter()
    {
        return $this->hasOne(Centres::class, 'id', 'center_id');
    }

protected $appends = ['can_access_center_details'];

public function getCanAccessCenterDetailsAttribute(): bool
{
    $isCoreDivision = (int) $this->is_core_or_legs_user === 1 && $this->company_division_name === 'core';

    $userTypes = json_decode($this->user_type, true) ?? [];
    $isSuperAdmin = is_array($userTypes) && in_array('Super Admin', $userTypes);

    return $isCoreDivision || $isSuperAdmin;
}

}
