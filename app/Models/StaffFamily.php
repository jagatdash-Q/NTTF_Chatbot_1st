<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;

class StaffFamily extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'staff_family';

    protected $fillable = [
        'employee_id',
        'family_name',
        'family_dob',
        'family_age',
        'family_occupation',
        'family_relationship',
         'created_by',
        'updated_by'
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
