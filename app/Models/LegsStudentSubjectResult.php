<?php

namespace App\Models;

use App\Models\LegsProgrammeModels\LegsSemesters;
use App\Models\LegsProgrammeModels\LegsSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;
use Illuminate\Support\Facades\DB;

class LegsStudentSubjectResult extends Model
{
    use HasFactory;

    protected $table = 'legs_student_subject_result';

    /**
     * The primary key for the model.
     * Composite key is defined at the database level.
     */
    public $incrementing = false;
    protected $primaryKey = null;

    protected $fillable = [
        'uuid',
        'student_code',
        'legs_semesters_id',
        'exam_type',
        'legs_subjects_subject_code',
        'gr',
        'gp',
        'total_mark',
        'mark_secured',
        'pass_mark',
        'legs_student_batches_id',
        'created_at',
        'updated_at',
        'sess_mark',
        'sem_mark',
        'result_code',
        'is_old_record_import',
        'sess_full_mark',
        'sem_full_mark',
        'sem_pass_mark',
        'sess_pass_mark'
    ];

    protected $casts = [
        'gp' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
    // Define the composite primary key behavior
    public function getKeyName()
    {
        return ['student_code', 'legs_semesters_id', 'exam_type', 'legs_subjects_subject_code'];
    }

    // Disable auto-incrementing as we have a composite primary key
    public function getIncrementing()
    {
        return false;
    }

    // Define the key type
    public function getKeyType()
    {
        return 'string';
    }

    /**
     * Get the semester associated with the subject result.
     */
    public function semester(): BelongsTo
    {
        return $this->belongsTo(LegsSemesters::class, 'legs_semesters_id');
    }

    /**
     * Get the subject associated with the result.
     */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(LegsSubject::class, 'legs_subjects_subject_code', 'subject_code');
    }



    /**
     * Return 'F' if any grade (gr) is outside A–E; otherwise return 'P'.
     */
    public static function computePassFail(
        string $studentCode,
        int $batchId,
        int $semesterId,
        string $examType
    ): string {
        // Count rows where gr is not A–E
        $failCount = self::where('student_code', $studentCode)
            ->where('legs_student_batches_id', $batchId)
            ->where('legs_semesters_id', $semesterId)
            ->where('exam_type', $examType)
            ->whereNotIn('gr', ['A', 'B', 'C', 'D', 'E'])
            ->count();

        return ($failCount > 0) ? 'F' : 'P';
    }


    /**
     * Compute the grand total of mark_secured for a given student/batch/semester/exam.
     *
     * @param  string  $studentCode
     * @param  int     $batchId
     * @param  int     $semesterId
     * @param  string  $examType
     * @return float
     */
    // public static function computeGrandTotal(
    //     string $studentCode,
    //     int $batchId,
    //     int $semesterId,
    //     string $examType
    // ): float {
    //     return (float) self::where('student_code', $studentCode)
    //         ->where('legs_student_batches_id', $batchId)
    //         ->where('legs_semesters_id', $semesterId)
    //         ->where('exam_type', $examType)
    //         ->sum('mark_secured');
    // }

    public static function computeGrandTotal(string $studentCode, int $batchId, int $semesterId, string $examType): float
    {
        $sum = DB::table('legs_student_subject_result')
            ->where('student_code', $studentCode)
            ->where('legs_student_batches_id', $batchId)
            ->where('legs_semesters_id', $semesterId)
            ->where('exam_type', $examType)
            ->sum('mark_secured');

        return (float) $sum;
    }
    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->uuid = new UuidV7();
        });
    }
}
