<?php

namespace App\Models;

use App\Models\CommonModels\Centres;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;


class FeePlan extends Model
{
    use SoftDeletes;
    use LogsActivity, HasFactory;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();

    }

    protected $fillable = [
        'centre_id',
        'course_id',
        'academic_year',
        'semester',
        'fee_quota',
        'category',
        'gender',
        'is_bvoc',
        'is_nocn_ncc',
        'is_nttf_alumni',
        'total_amount',
        'fee_head_id'
    ];


    public function centre()
    {
        return $this->belongsTo(Centres::class);
    }

    // public function course()
    // {
    //     return $this->belongsTo(Course::class);
    // }
}
