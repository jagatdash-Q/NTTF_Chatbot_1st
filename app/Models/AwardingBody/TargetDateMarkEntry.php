<?php

namespace App\Models\AwardingBody;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TargetDateMarkEntry extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'center_id',
        'programm_id',
        // 'stream_id',
        // 'semester_id',
        // 'academic_year_id',a
        'module',
        'mail_sent',
        'target_date',
        'cycly_type',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'target_date' => 'date',
    ];
}
