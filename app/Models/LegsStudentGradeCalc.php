<?php

namespace App\Models;

use App\Models\LegsProgrammeModels\LegsSemesters;
use App\Models\LegsProgrammeModels\LegsStudentBatch;
use App\Models\LegsProgrammeModels\LegsSubject;
use App\Models\LegsProgrammeModels\StudentAdmissionLegs;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegsStudentGradeCalc extends Model
{
    use HasFactory;

    protected $table = 'legs_student_grade_calc';

    /**
     * The primary key for the model.
     * Composite key is defined at the database level.
     */
    public $incrementing = false;
    protected $primaryKey = null;

    protected $fillable = [
        'student_code',
        'batch_id',
        'legs_semesters_id',
        'exam_type',
        'legs_subjects_subject_code',
        'total_mark',
        'mark_secured',
        'pass_mark'
    ];

    protected $casts = [
        'batch_id' => 'integer',
        'legs_semesters_id' => 'integer',
        'total_mark' => 'decimal:2',
        'mark_secured' => 'decimal:2',
        'pass_mark' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Validation rules for creating/updating grades
     */
    public static function rules()
    {
        return [
            'student_code' => 'required|string',
            'batch_id' => 'required|exists:legs_student_batches,id',
            'legs_semesters_id' => 'required|exists:legs_semesters,id',
            'exam_type' => 'required|in:sem,supl_1,supl_2,supl_3',
            'legs_subjects_subject_code' => 'required|string',
            'total_mark' => 'required|numeric|min:0',
            'mark_secured' => 'required|numeric|min:0',
            'pass_mark' => 'required|numeric|min:0',
        ];
    }

    /**
     * Get the student associated with this grade.
     */
    public function student()
    {
        return $this->belongsTo(StudentAdmissionLegs::class, 'student_code', 'student_code');
    }

    /**
     * Get the batch associated with this grade.
     */
    public function batch()
    {
        return $this->belongsTo(LegsStudentBatch::class, 'batch_id');
    }

    /**
     * Get the semester associated with the grade calculation.
     */
    public function semester(): BelongsTo
    {
        return $this->belongsTo(LegsSemesters::class, 'legs_semesters_id');
    }

    /**
     * Get the subject associated with the grade calculation.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(LegsSubject::class, 'legs_subjects_subject_code', 'subject_code');
    }

    /**
     * Determine if the student passed this subject.
     *
     * @return bool
     */
    public function isPassed()
    {
        return $this->mark_secured >= $this->pass_mark;
    }

    /**
     * Create a new grade record with correct parameter ordering
     * 
     * @param array $attributes
     * @return static
     */
    public static function createGrade(array $attributes)
    {
        // Ensure the batch_id is properly set and all fields are in the correct order
        $data = [
            'student_code' => $attributes['student_code'] ?? null,
            'batch_id' => $attributes['batch_id'] ?? null,
            'legs_semesters_id' => $attributes['legs_semesters_id'] ?? null,
            'exam_type' => $attributes['exam_type'] ?? null,
            'legs_subjects_subject_code' => $attributes['legs_subjects_subject_code'] ?? null,
            'total_mark' => $attributes['total_mark'] ?? null,
            'mark_secured' => $attributes['mark_secured'] ?? null,
            'pass_mark' => $attributes['pass_mark'] ?? null,
        ];

        // Filter out null values
        $data = array_filter($data, function ($value) {
            return $value !== null;
        });

        // Create and return the record
        return self::create($data);
    }
}
