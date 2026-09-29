<?php

namespace App\Models\AcademicSectionModels;

//use App\HasPermissionsTrait;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class Electives extends Model
{
    use HasFactory ,SoftDeletes,LogsActivity;
    protected $fillable = ['elective_group','sub_id','subject_type','elective_type'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
       
    }
}
