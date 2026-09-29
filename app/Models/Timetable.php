<?php

namespace App\Models;

use App\Models\LegsProgrammeModels\LegsSemesters;
use App\Models\LegsProgrammeModels\LegsStudentBatch;
use App\Models\LegsProgrammeModels\LegsSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Timetable extends Model
{
    use HasFactory;

    protected $fillable = [
        'legs_zone_id',
        'industry_partner_id',
        'batch',
        'program_id',
        'semester_id',
        'location',
        'other_location',
        'subject',
        'staff',
        'day_of_week',
        'start_time',
        'end_time',
        'start_date',
        'end_date',
        'recurrence_type','lms'
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i',
        'end_time' => 'datetime:H:i',
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function subject(): BelongsTo
    {
        return $this->belongsTo(LegsSubject::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(LegsStudentBatch::class);
    }


    public function semester()
    {
        return $this->belongsTo(LegsSemesters::class);
    }
}
