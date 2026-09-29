<?php

namespace App\Models\IndustryPartnerModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class KeyProcess extends Model
{
    use HasFactory,LogsActivity;
    protected $table = 'key_processes'; 

    protected $fillable = [
        'industry_partner_master_details_id',
        'kay_process_name',
        'kay_process_desc',
        'status'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
       
    }
}
