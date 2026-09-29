<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;

class CoreCompanyPlant extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'core_company_plants';

    protected $fillable = [
        'company_id',
        'address_id',
        'contact_id',
        'plant_name',
        'plant_location',
        'products_manufactured',
        'no_of_shifts',
        'entry_date',
        'establishment_year',
        'tan_number',
        'bank_number',
        'turnover',
        'gst_no',
        'pan_number',
        'service_tax_reg',
        'employee_size',
        'no_of_employees_regular',
        'no_of_employees_contractual',
        'no_of_employees_apprentice_trainees',
        'lab_facility_available',
        'no_of_classrooms_available',
        'created_by',
        'updated_by',
    ];
   public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
