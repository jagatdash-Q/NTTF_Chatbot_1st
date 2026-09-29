<?php

namespace App\Models\QuizModels;

use App\Models\CommonModels\TestTimeSlot;
use App\Models\QuizModels\ExamResults;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Exam extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;
    protected $fillable = [
        'exam_name',
        'exam_date',
        'time_slot_id',
        'time_slot',
        'total_mark',
        'passing_mark_num',
        'passing_mark_percentage',
        'is_active',
        'remarks',
        'status',
        'negative_mark',
        'duration',
        'no_of_ques',
        'exam_type',
        'student_id',
        'branch'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();

    }

    public function timeSlot()
    {
        return $this->belongsTo(TestTimeSlot::class, 'time_slot_id', 'id');
    }
    public function questions()
    {
        return $this->belongsToMany(Quiz::class, 'quiz_question_exams', 'exam_id', 'quizzes_quiz_id');
    }

    public function examResult()
    {
        return $this->hasOne(ExamResults::class);
    }

    public function studentAnswers()
    {
        return $this->hasMany(StudentAnswers::class);
    }

}
