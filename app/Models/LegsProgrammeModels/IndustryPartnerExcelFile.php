<?php

namespace App\Models\LegsProgrammeModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class IndustryPartnerExcelFile extends Model
{
    use HasFactory,  LogsActivity;
    protected $fillable = [
        'industry_partner_id',
        'file_path',
        'status'
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
