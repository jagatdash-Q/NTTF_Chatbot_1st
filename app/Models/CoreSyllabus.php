<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CoreSyllabus extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'uuid',
        'programe_id',
        'semester_id',
        'subject_id',
        'topic_group',
        'topic_name',
        'topic_code',
        'start_date',
        'end_date',
        'no_of_class',
        'description',
        'version',
        'update_type',
        'version_update_date',
        'program_type',
        'year_month',
        'topic_hour',
        'subtopic', // ✅ ONLY THIS
    ];

    // 🔥 THIS IS THE KEY FIX
    protected $casts = [
        'subtopic' => 'array',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
