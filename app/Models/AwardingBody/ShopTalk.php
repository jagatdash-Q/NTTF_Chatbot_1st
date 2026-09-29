<?php

namespace App\Models\AwardingBody;

use App\Models\AcademicSectionModels\Semesters;
use App\Models\CommonModels\CenterPrograme;
use App\Models\CommonModels\Centres;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class ShopTalk extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    public $table = 'shop_talks';

    protected $fillable = [
        'center_id', 'programm_id', 'section', 'semester_id', 'cycle', 'token_number', 'name', 'shop_talk_topic_name', 'allocated_date', 'actual_complitation_date', 'status', 'mark','stream_id','academic_year_id','student_id','created_by','updated_by'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();

    }

    public function center()
    {
        return $this->belongsTo(
            Centres::class,
            'center_id'
        );
    }

    public function program()
    {
        return $this->belongsTo(
            CenterPrograme::class,
            'programm_id'
        );
    }

    public function semester()
    {
        return $this->belongsTo(
            Semesters::class,
            'semester_id'
        );
    }
}
