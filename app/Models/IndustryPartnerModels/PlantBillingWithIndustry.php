<?php

namespace App\Models\IndustryPartnerModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class PlantBillingWithIndustry extends Model
{
    use HasFactory,LogsActivity;
    protected $table = 'plant_billing_with_industries';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'industrypartner_master_id',
        'billing_details_id',
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
       
    }
}
