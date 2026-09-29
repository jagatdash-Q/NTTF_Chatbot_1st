<?php

namespace App\Models\AwardingBody;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SectionIncharge extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'center_id', 'center_name',
        'program_id', 'program_name',
        'stream_id', 'stream',
        'semester_id', 'semester',
        'academic_year_id', 'academic_year_span',
        'batch_id', 'batch',
        'section_id', 'section',
        'user_id', 'user',
    ];
}
