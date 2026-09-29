<?php

namespace App\Models;

use App\Models\Skill;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSkill extends Model
{

    protected $fillable = ['user_id', 'skill_id'];

    /**
     * Get the user that owns the skill.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the skill for this relationship.
     */
    public function skill(): BelongsTo
    {
        return $this->belongsTo(Skill::class);
    }
}
