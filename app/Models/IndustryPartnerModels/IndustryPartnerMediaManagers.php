<?php

namespace App\Models\IndustryPartnerModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class IndustryPartnerMediaManagers extends Model
{
    use HasFactory,LogsActivity;
    protected $table = 'industry_partner_media_managers'; 

    protected $fillable = [
        'media_manager_id',
        'industry_partner_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
       
    }
}
