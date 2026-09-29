<?php

namespace App\Models\AcademicSectionModels;

use App\Models\CommonModels\Centres;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CenterWiseCourseWithCategoryTypes extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['center_id', 'stream_id','academic_programme_id','industry_partner_id','zone_cat_id','zone_id','batch_year','batch_month'];
    public $table = 'center_wise_course_with_category_types';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function getCourse(): HasOne
    {
        return $this->hasOne(Centres::class, 'id', 'center_id');
    }

    public function getCenterWiseCourse(): HasOne
    {
        return $this->hasOne(CollegeAcademicProgrammeStreams::class, 'id', 'stream_id');
    }
}
