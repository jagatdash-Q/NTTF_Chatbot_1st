<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;


class StaffQualification extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'staff_qualifications';

    protected $fillable = [
        'employee_id',
        'specialization',
        'university',
        'institute_name',
        'from_date',
        'to_date',
        'percentage',
        'cgpa',
        'created_by',
        'updated_by'
    ];
       public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
    
}
