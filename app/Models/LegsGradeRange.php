<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegsGradeRange extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'semester_id',
        'subject_code',
        'grade',
        'min_mark',
        'max_mark',
        'description',
        'is_pass'
    ];

    /**
     * Get the grade for a specific mark and subject
     *
     * @param int $batchId
     * @param int $semesterId
     * @param string $subjectCode
     * @param float $markSecured
     * @return string|null
     */
    public static function getGradeForMark(int $batchId, int $semesterId, string $subjectCode, float $markSecured): ?string
    {
        // Find the grade range that contains this mark
        $gradeRange = self::where('batch_id', $batchId)
            ->where('semester_id', $semesterId)
            ->where('subject_code', $subjectCode)
            ->where('min_mark', '<=', $markSecured)
            ->where('max_mark', '>=', $markSecured)
            ->first();

        return $gradeRange ? $gradeRange->grade : null;
    }

    /**
     * Get batch relationship
     */
    public function batch()
    {
        return $this->belongsTo('App\Models\LegsProgrammeModels\LegsStudentBatch', 'batch_id');
    }

    /**
     * Get semester relationship
     */
    public function semester()
    {
        return $this->belongsTo('App\Models\LegsProgrammeModels\LegsSemester', 'semester_id');
    }
}
