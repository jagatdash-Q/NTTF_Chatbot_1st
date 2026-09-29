<?php

namespace App\Models;

use App\Models\LegsProgrammeModels\LegsSemesters;
use App\Models\LegsProgrammeModels\LegsStudentBatch;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegsSemesterStat extends Model
{

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'legs_semester_stats';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uuid',
        'batch_id',
        'semester_id',
        'exam_type',
        'publish_date',
        'created_by',
        'approved_by',
        'is_approved',
        'is_email_sent',
        'is_downloadable',
        'downloaded_by',
        'exam_name',
        'is_old_record',
        'is_processing_disabled'
    ];


    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'publish_date' => 'datetime',
        'is_approved' => 'boolean',
        'is_email_sent' => 'boolean',
        'is_downloadable' => 'boolean',
        'downloaded_by' => 'array',
    ];

    /**
     * Get the batch that owns the semester stat.
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(LegsStudentBatch::class);
    }

    /**
     * Get the semester that owns the semester stat.
     */
    public function semester(): BelongsTo
    {
        return $this->belongsTo(LegsSemesters::class, 'legs_semesters_id');
    }

    /**
     * Get the user who approved the semester stat.
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope a query to only include approved stats.
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope a query to only include downloadable stats.
     */
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
