<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;


class CoreSalaryUpdate extends Model
{
    use HasFactory, LogsActivity;


    protected $table = 'core_salaryupdate';

    protected $fillable = [
        'hiring_id', // ✅ NEW
        'company_id',
        'company_name',
        'designation',
        'actual_monthly_salary',
        'ctc',
        'benefits_offered',
        'reporting_date',
        'end_of_probation_date',
        'placement_status',
        'remarks',
        'created_by',
        'updated_by',
        'requirement_for',
        'ctc_in_words',
    ];

    // Optional: cast dates to Carbon instances
    // protected $casts = [
    //     'reporting_date' => 'date',
    //     'end_of_probation_date' => 'date',
    // ];

       public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
