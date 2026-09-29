<?php

namespace App\Models;

use App\Models\CoreProgrammeModels\Studentlead;
use App\Models\QuizModels\Exam;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CoreTestExamTempResults extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = [
        'student_id',
        'exam_id',
        'time_covered',
        'started_at',
        'last_ping_at',
        'total_seconds'
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'last_ping_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    public function answers()
    {
        return $this->hasMany(CoreTestExamTempSave::class, 'core_test_exam_temp_results_id');
    }

    public function getStudentDetails()
    {
        return $this->hasOne(Studentlead::class, 'id','student_id');
    }

    public function getExamDetails()
    {
        return $this->hasOne(Exam::class, 'id','exam_id');
    }
}
