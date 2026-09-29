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


class CollegeAcademicProgrammewithBranches extends Model
{
    use HasFactory;
    use HasFactory ,SoftDeletes,LogsActivity;
    protected $fillable = ['college_academic_programme','college_academic_stream_branch'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
       
    }
}
