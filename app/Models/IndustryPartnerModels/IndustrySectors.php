<?php


namespace App\Models\IndustryPartnerModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class IndustrySectors extends Model
{
    use HasFactory,LogsActivity;
    protected $fillable = ['name', 'status'];
    public $table = 'industry_sectors';
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
       
    }
    
}
