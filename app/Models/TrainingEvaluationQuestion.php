<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\TrainingEvaluationRating;



class TrainingEvaluationQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_text',
        'question_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function ratings(): HasMany
    {
        return $this->hasMany(TrainingEvaluationRating::class, 'question_id');
    }
}
