<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegsStudentFinalResult extends Model
{
    use HasFactory;

    protected $table = 'legs_student_final_result';

    protected $primaryKey = 'student_code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'student_code',
        'cgpa',
        'cgpa_class',
        'result_date',
        'published_by',
    ];

    protected $casts = [
        'cgpa' => 'decimal:2',
        'result_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user who published the result.
     */
    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }
}