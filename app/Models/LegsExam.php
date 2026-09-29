<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegsExam extends Model
{
    protected $fillable = [
        'exam_id',
        'created_by',
    ];

    public function questions()
    {
        return $this->hasMany(LegsQuiz::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
