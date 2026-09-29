<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrainingEvaluationRating extends Model
{
    use HasFactory;

    protected $fillable = [
        'submission_id',
        'question_id',
        'rating',
    ];

    public function submission(): BelongsTo
    {
        return $this->belongsTo(TrainingEvaluationSubmission::class);
    }

    public function question()
    {
        return $this->belongsTo(TrainingEvaluationQuestion::class, 'question_id');
    }
}
