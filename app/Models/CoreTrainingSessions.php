<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use App\Models\CoreProgrammeModels\StudentAdmission;

class CoreTrainingSessions extends Model
{
     use HasFactory;
     protected $fillable = [
        'month_year',
        'stream_type',
        'stream_ids',
        'date_from',
        'date_to',
        'day_session',
        'time_from',
        'time_to',
        'subject_program_type',
        'reference_id',
        'sem_type',
        'source_of_identification',
        'remarks',
        'emp_ids'
    ];

    protected $casts = [
        'stream_ids' => 'array',
        'date_from' => 'date',
        'date_to' => 'date',
        'emp_ids' => 'array',
    ];
     public $table = 'core_training_sessions';
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

}



