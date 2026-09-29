<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;

class CoreCompany extends Model
{
    use HasFactory, LogsActivity;
    protected $table = 'core_company';
    protected $fillable = [
        'company_name',
        'company_code',
        'entry_date',
        'industry_sector',
        'key_processes',
        'products_manufactured',
        'establishment_year',
        'company_website',
        'turn_over',
        'employee_size',
        'address_id',
        'contact_id',
        'created_by',
        'updated_by',       
        'remarks',
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
