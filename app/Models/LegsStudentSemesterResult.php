<?php

namespace App\Models;

use App\Models\LegsProgrammeModels\LegsSemesters;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\UuidV7;

class LegsStudentSemesterResult extends Model
{
    use HasFactory;

    protected $table = 'legs_student_semester_result';

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
        'gr_total',
        'result',
        'result_code',
        'sgpa',
        'sgpa_class',
        'cpga',
        'cgpa_class',
        'uuid'

    ];

    protected $casts = [
        'gr_total' => 'decimal:2',
        'sgpa' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->uuid = new UuidV7();
        });
    }

    /**
     * Get the semester associated with the semester result.
     */
    public function semester(): BelongsTo
    {
        return $this->belongsTo(LegsSemesters::class, 'legs_semesters_id');
    }
}
