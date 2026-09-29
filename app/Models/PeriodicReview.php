<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PeriodicReview extends Model
{
    use HasFactory;
    protected $fillable = [
        'industry_partner_id',
        'date',
        'place',
        'duration',
        'agenda',
        'meeting_notes',
        'distribution',
        'outcome',
        'next_meeting',
        'to_be_heald',
        'minutes_prepair',
        'zone_id',
        'batch',
        'program_id',
        'semester_id'
    ];

    public function representatives()
    {
        return $this->hasMany(SelectedRepresentative::class, 'review_id');
    }

    public function nttfrepresentatives()
    {
        return $this->hasMany(NttfRepresentative::class, 'review_id');
    }
}
