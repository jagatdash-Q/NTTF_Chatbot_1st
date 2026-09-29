<?php

namespace App\Models;

use App\Models\CoreSyllabus;
use Illuminate\Database\Eloquent\Model;

class CoreTheoryTopicProgress extends Model
{
    protected $table = 'core_theory_topic_progress';

    protected $fillable = [
        'topic_id',
        'is_completed',
        'completed_at',
        'subject_id',
        'batch_id',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function topic()
    {
        return $this->belongsTo(CoreSyllabus::class, 'topic_id');
    }
}
