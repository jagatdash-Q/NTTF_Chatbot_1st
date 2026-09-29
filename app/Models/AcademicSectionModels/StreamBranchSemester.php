<?php

namespace App\Models\AcademicSectionModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\AcademicSectionModels\Semesters;


class StreamBranchSemester extends Model
{
    use HasFactory ,SoftDeletes,LogsActivity;

    protected $fillable = ['stream_id','branch_id','semester_id'];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
       
    }
    public function semester()
    {
        return $this->belongsTo(Semesters::class, 'semester_id');
    }
}
