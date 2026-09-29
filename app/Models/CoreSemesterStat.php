<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\AcademicSectionModels\CenterWiseCourseWithCategoryTypes;
use App\Models\AcademicSectionModels\Semesters;
class CoreSemesterStat extends Model
{
    use HasFactory;

    protected $table = 'core_semester_stats';

    protected $fillable = [
        'uuid',
        'centre_id',
        'program_id',
        'stream_id',
        'academic_year_id',
        'academic_year',
        // 'lock_result',
        // 'is_publish_to_staff',
        // 'is_publish_to_student',
        // 'final_publish_to_staff',
        // 'final_publish_to_student',
        'semester_id',
        'total_results_assigned',
        'total_eval_results',
        'exam_type',
        'core_exam_type',
        'suppl_no',
        'exam_name',
        'publish_date',
        'created_by',
        'approved_by',
        'is_approved',
        'is_email_sent',
        'is_downloadable',
        'downloaded_by',
        'result_update',
        'is_old_record',
        'is_processing_disabled',
    ];

    protected $casts = [
        'publish_date' => 'date',
        'downloaded_by' => 'array',
        'is_approved' => 'boolean',
        'is_email_sent' => 'boolean',
        'is_downloadable' => 'boolean',
        'is_old_record' => 'boolean',
        'is_processing_disabled' => 'boolean',
    ];

    public function centre(): BelongsTo
    {
        return $this->belongsTo(CenterWiseCourseWithCategoryTypes::class);
    }

    /**
     * Get the semester that owns the semester stat.
     */
    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semesters::class, 'legs_semesters_id');
    }

    /**
     * Get the user who approved the semester stat.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }


    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

        public function scopeDownloadable($query)
    {
        return $query->where('is_downloadable', true);
    }

    /**
     * Check if the stat has been downloaded by a specific user.
     */
    public function isDownloadedBy($userId): bool
    {
        if (!$this->downloaded_by) {
            return false;
        }

        foreach ($this->downloaded_by as $download) {
            if (isset($download['user_id']) && $download['user_id'] == $userId) {
                return true;
            }
        }

        return false;
    }
}
