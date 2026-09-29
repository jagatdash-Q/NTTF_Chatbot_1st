<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegsGradeBoundary extends Model
{
    
    protected $fillable = ['grade_id', 'test_type', 'min_marks', 'max_marks'];
    
    protected $casts = [
        'min_marks' => 'integer',
        'max_marks' => 'integer'
    ];
    
    public function grade()
    {
        return $this->belongsTo(LegsGrade::class, 'grade_id');
    }

     /**
     * Scope to filter by grade
     */
    public function scopeByGrade($query, $gradeId)
    {
        return $query->where('grade_id', $gradeId);
    }

    /**
     * Check if a mark falls within this boundary
     */
    public function containsMark($mark)
    {
        return $mark >= $this->min_marks && $mark <= $this->max_marks;
    }

    /**
     * Get test type display name
     */
    public function getTestTypeDisplayAttribute()
    {
        return str_replace('_', ' ', $this->test_type);
    }
}
