<?php

namespace App\Models\LegsProgrammeModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;


class LegsStreamBranchSemester extends Model
{
    use HasFactory ,SoftDeletes,LogsActivity;

    protected $fillable = ['stream_id','branch_id','semester_id'];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
       
    }
}
