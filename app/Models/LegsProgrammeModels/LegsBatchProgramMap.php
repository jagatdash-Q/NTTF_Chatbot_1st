<?php

namespace App\Models\LegsProgrammeModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LegsBatchProgramMap extends Model
{
    use HasFactory,  LogsActivity;
    protected $fillable = ['batch_id','program_id'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();

    }
}
