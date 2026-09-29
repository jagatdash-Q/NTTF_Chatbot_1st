<?php

namespace App\Models\AcademicSectionModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\SoftDeletes;

class Topic extends Model
{
    use HasFactory ,SoftDeletes,LogsActivity;

    protected $fillable = ['stream','branch','semester','subject','topic_name','topic_desc'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
       
    }
}
