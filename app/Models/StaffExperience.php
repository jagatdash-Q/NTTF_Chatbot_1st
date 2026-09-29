<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;

class StaffExperience extends Model
{
    use HasFactory, LogsActivity;

 
    protected $table = 'staff_experiences';

    protected $fillable = [
        'employee_id',
        'year_of_experience',
        'no_of_years_worked',
        'old_company_name',
        'designation',
        'old_company_from_date',
        'old_company_to_date',
        'status',
        'created_by',
        'updated_by',
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

}
