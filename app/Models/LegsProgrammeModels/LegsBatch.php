<?php

namespace App\Models\LegsProgrammeModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LegsBatch extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = ['subject_id','batch_year'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();

    }
}
