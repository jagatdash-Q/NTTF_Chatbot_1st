<?php

namespace App\Models\AcademicSectionModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Batch extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = ['subject_id','batch_year'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();

    }
}
