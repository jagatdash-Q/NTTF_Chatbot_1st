<?php

namespace App\Models\LegsProgrammeModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LegsPracticals extends Model
{
    use HasFactory ,SoftDeletes,LogsActivity;
    protected $fillable = ['elective_group','credit','hour','practical_name','practical_code','sub_id'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
       
    }
}
