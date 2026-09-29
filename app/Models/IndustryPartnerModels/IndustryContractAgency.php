<?php

namespace App\Models\IndustryPartnerModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class IndustryContractAgency extends Model
{
    use HasFactory, LogsActivity;
    protected $table = 'industry_contract_agencies';

    protected $fillable = [
        'industry_partner_id',
        'industry_partner_contract_id',
        'agency_name',
        'status',
        'created_by',
        'updated_by',

    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
