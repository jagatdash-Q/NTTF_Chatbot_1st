<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LatestCoreSyllabus extends Model
{

    use LogsActivity;
    protected $fillable = ['syllabus_uuid', 'version'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();

    }
}
