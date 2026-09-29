<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessionPlan extends Model
{
    protected $fillable = [
        'zone_id',
        'industry_partner_id',
        'batch',
        'program_id',
        'semester_id',
        'instructor_id',
        'subject_id',
        'topic_id',
        'date',
        'lession_objective',
        'lession_introduction',
        'teaching_aids',
        'main_point',
        'reinforcement',
        'assignment',
        'next_lession',
        'signature',
        'approve_by'

    ];
}
