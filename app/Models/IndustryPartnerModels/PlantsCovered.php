<?php

namespace App\Models;

namespace App\Models\IndustryPartnerModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class PlantsCovered extends Model
{
    use HasFactory,LogsActivity;
    protected $fillable = ['name', 'status', 'address_id', 'contact_id'];
    public $table = 'plants_covereds';
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
       
    }
}
