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

class Rating extends Model
{
     use HasFactory, LogsActivity, SoftDeletes;

    protected $table = 'ratings';

protected $fillable = ['center_id','no_of_zone','no_of_cell','final_percentage','audit_date','upload_excel','created_by','updated_by'];

    protected $casts = [
        'final_percentage' => 'float',
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


}
