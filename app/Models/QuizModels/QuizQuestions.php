<?php

namespace App\Models\QuizModels;

use App\Models\AcademicSectionModels\CollegeAcademicProgrammeStreamBranches;
use App\Models\AcademicSectionModels\CollegeAcademicProgrammeStreams;
use App\Models\AcademicSectionModels\Semesters;
use App\Models\AcademicSectionModels\Subjects;
use App\Models\AcademicSectionModels\Topic;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class QuizQuestions extends Model
{
    use HasFactory ,SoftDeletes,LogsActivity;
    protected $fillable = ['stream_id','branch_id','subject_id','semester_id','topic_id','question_text','difficulty_level','created_by','updated_by'];


    public function stream()
    {
        return $this->belongsTo(CollegeAcademicProgrammeStreams::class, 'stream_id');
    }

    public function branch()
    {
        return $this->belongsTo(CollegeAcademicProgrammeStreamBranches::class, 'branch_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subjects::class, 'subject_id');
    }

    public function topic()
    {
        return $this->belongsTo(Topic::class, 'topic_id');
    }


    public function semester()
    {
        return $this->belongsTo(Semesters::class, 'semester_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
       
    }
}
