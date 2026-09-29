<?php

namespace App\Models\IndustryPartnerModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class KeyProcessIndustryPartner extends Model
{
    use HasFactory,LogsActivity;
    protected $table = 'key_process_industry_partners';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'keyprocess_id',
        'industry_partner_master_id',
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
       
    }
}
