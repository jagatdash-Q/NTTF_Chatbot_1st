<?php

namespace App\Models\AcademicSectionModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class CourseMappingsWithCoreandIndustryPartner extends Model
{
    use HasFactory, Notifiable ,SoftDeletes,LogsActivity;
    protected $fillable = [];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
       
    }
}
