<?php

namespace App\Models;

use App\Models\LegsProgrammeModels\Syllabus;
use Illuminate\Database\Eloquent\Model;

class PracticalTopicProgress extends Model
{
    protected $fillable = [
        'topic_id',
        'is_completed',
        'completed_at',
        'subject_id',
        'batch_id'
    ];

    public function topic()
    {
        return $this->belongsTo(Syllabus::class);
    }
}
