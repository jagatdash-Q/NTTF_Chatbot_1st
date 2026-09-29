<?php

namespace App\Models\IndustryPartnerModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class IndustryPartnerContractManagingCommittee extends Model
{
    use HasFactory,LogsActivity;
    protected $fillable = [];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
       
    }
}
