<?php

namespace App\Models\AcademicSectionModels;


//use App\HasPermissionsTrait;
use App\Models\CommonModels\Centres;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class CollegeAcademicProgrammeStreams extends Model
{
    use HasFactory ,SoftDeletes,LogsActivity;
    protected $fillable = ['academic_programme_id','stream_name','stream_code','status','token_number'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
       
    }
    public function centres()
    {
        return $this->belongsToMany(Centres::class, 'center_wise_course_with_category_types', 'stream_id', 'center_id');
    }
}
