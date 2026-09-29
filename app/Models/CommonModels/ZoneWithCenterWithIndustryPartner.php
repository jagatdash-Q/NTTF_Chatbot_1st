<?php

namespace App\Models\CommonModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ZoneWithCenterWithIndustryPartner extends Model
{
    use HasFactory ,LogsActivity;
    public $table = 'zone_with_center_with_industry_partners';
    protected $fillable = [
       'zone_id','center_id','industry_partner_master_id'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
       
    }
}
