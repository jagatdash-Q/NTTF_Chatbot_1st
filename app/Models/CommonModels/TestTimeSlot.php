<?php

namespace App\Models\CommonModels;

use App\Models\AcademicSectionModels\QuizType;
use App\Models\QuizModels\Exam;
use App\Models\QuizModels\Quiz;
use App\Services\ExamService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class TestTimeSlot extends Model
{
    use HasFactory, Notifiable, SoftDeletes, LogsActivity;
    protected $fillable = [
        'id',
        'uuid',
        'center',
        'start_time',
        'end_time',
        'exam_date',
        'time_span',
        'exam_id',
        'test_time_description',
        'is_active',
        'status',
        'created_by',
        'updated_by',
        'program_id'
    ];

    public function getCenter(): HasOne
    {
        return $this->hasOne(Centres::class, 'id', 'center');
    }
    public function exams()
    {
        return $this->hasMany(Exam::class, 'time_slot_id', 'id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }


    // protected static function booted()
    // {
    //     static::created(function ($timeSlot) {
    //         try {
    //             // Exam configurations
    //             $examConfigs = [
    //                 'Diploma' => [
    //                     'mathematicsCount' => env('DIPLOMA_MATH'),
    //                     'scienceCount' => env('DIPLOMA_SCIENCE'),
    //                     'aptitudeCount' => env('DIPLOMA_APTITUDE'),
    //                     'examDuration' => env('DIPLOMA_EXAM_DURATION'),
    //                     'totalQuestions' => env('DIPLOMA_NO_OF_QUES'),
    //                     'examName' => env('DIPLOMA_EXAM_NAME'),
    //                     'totalMark' => env('DIPLOMA_TOTAL_MARK'),
    //                     'negativeMark' => env('DIPLOMA_NEGATIVE_MARK'),
    //                     'passingMark' => env('DIPLOMA_PASSING_MARK'),
    //                     'passingPercentage' => env('DIPLOMA_PASSING_PERCENTAGE'),
    //                     'examType' => env('DIPLOMA_ID'),
    //                 ],
    //                 'PostDiplomaMechanical' => [
    //                     'aptitudeCount' => env('PD_MECHANICAL_APTITUDE'),
    //                     'toolDesignCount' => env('PD_MECHANICAL_TOOL_DESIGN'),
    //                     'examDuration' => env('PD_EXAM_DURATION'),
    //                     'totalQuestions' => env('PD_NO_OF_QUES'),
    //                     'examName' => env('PD_MECHANICAL_EXAM_NAME'),
    //                     'totalMark' => env('PD_TOTAL_MARK'),
    //                     'negativeMark' => env('PD_NEGATIVE_MARK'),
    //                     'passingMark' => env('PD_PASSING_MARK'),
    //                     'passingPercentage' => env('PD_PASSING_PERCENTAGE'),
    //                     'examType' => env('PD_MECHANICAL_ID'),
    //                 ],
    //                 'PostDiplomaNonMechanical' => [
    //                     'aptitudeCount' => env('PD_NON_MECHANICAL_APTITUDE'),
    //                     'electronicsCount' => env('PD_NON_MECHANICAL_ELECTRONICS'),
    //                     'examDuration' => env('PD_EXAM_DURATION'),
    //                     'totalQuestions' => env('PD_NO_OF_QUES'),
    //                     'examName' => env('PD_NON_MECHANICAL_EXAM_NAME'),
    //                     'totalMark' => env('PD_TOTAL_MARK'),
    //                     'negativeMark' => env('PD_NEGATIVE_MARK'),
    //                     'passingMark' => env('PD_PASSING_MARK'),
    //                     'passingPercentage' => env('PD_PASSING_PERCENTAGE'),
    //                     'examType' => env('PD_NON_MECHANICAL_ID'),
    //                 ],
    //                 'PostGraduate' => [
    //                     'engineeringDrawingCount' => env('PG_ENGINEERING_DRAWING'),
    //                     'engineeringMaterialsCount' => env('PG_ENGINEERING_MATERIAL'),
    //                     'machineDesignCount' => env('PG_MACHINE_DESIGN'),
    //                     'manufacturingProcessesCount' => env('PG_MANUFACTURING_PROCESS'),
    //                     'staticsAndDynamicsCount' => env('PG_STATICS_AND_DYNAMICS'),
    //                     'strengthOfMaterialsCount' => env('PG_STRENGTH_OF_MATERIAL'),
    //                     'theoryofMachinesCount' => env('PG_THEORY_OF_MACHINE'),
    //                     'examDuration' => env('PG_EXAM_DURATION'),
    //                     'totalQuestions' => env('PG_NO_OF_QUES'),
    //                     'examName' => env('PG_EXAM_NAME'),
    //                     'totalMark' => env('PG_TOTAL_MARK'),
    //                     'negativeMark' => env('PG_NEGATIVE_MARK'),
    //                     'passingMark' => env('PG_PASSING_MARK'),
    //                     'passingPercentage' => env('PG_PASSING_PERCENTAGE'),
    //                     'examType' => env('PG_ID'),
    //                 ]
    //             ];

    //             foreach ($examConfigs as $examConfig) {
    //                 self::createExam($timeSlot, $examConfig);
    //             }
    //         } catch (\Exception $e) {
    //             Log::error('Error auto-creating exams: ' . $e->getMessage(), [
    //                 'timeSlot' => $timeSlot,
    //                 'stackTrace' => $e->getTraceAsString(),
    //             ]);
    //         }
    //     });
    // }

    // /**
    //  * Create an exam with the given configuration.
    //  */
    // private static function createExam($timeSlot, $config)
    // {
    //     try {
    //         // Fetch the relevant question types
    //         $questionTypes = QuizType::whereIn('alies_name', [
    //             'mathematics',
    //             'science',
    //             'aptitude',
    //             'tooldesign',
    //             'electronics',
    //             'engineeringdrawing',
    //             'engineeringmaterials',
    //             'machinedesign',
    //             'manufacturingprocesses',
    //             'staticsanddynamics',
    //             'strengthofmaterials',
    //             'theoryofmachines',
    //         ])->get()->keyBy('alies_name');

    //         $questionIds = [];

    //         foreach ($config as $key => $count) {
    //             if (str_ends_with($key, 'Count')) {
    //                 $subjectName = strtolower(str_replace('Count', '', $key));

    //                 if (isset($questionTypes[$subjectName])) {
    //                     $questionIds = array_merge(
    //                         $questionIds,
    //                         Quiz::where('quiz_subject_name', $questionTypes[$subjectName]->id)
    //                             ->inRandomOrder()
    //                             ->limit($count)
    //                             ->pluck('quiz_id')
    //                             ->toArray()
    //                     );
    //                 }
    //             }
    //         }

    //         // Validate the total number of questions
    //         if (count($questionIds) < $config['totalQuestions']) {
    //             Log::error('Not enough questions available', [
    //                 'examName' => $config['examName'],
    //                 'required' => $config['totalQuestions'],
    //                 'available' => count($questionIds),
    //             ]);
    //             return;
    //         }


    //         // Create the exam with a transaction
    //         DB::transaction(function () use ($timeSlot, $config, $questionIds) {
    //             $exam = Exam::create([
    //                 'exam_name' => $config['examName'],
    //                 'exam_date' => $timeSlot->exam_date,
    //                 'time_slot_id' => $timeSlot->id,
    //                 'time_slot' => $timeSlot->start_time . ' - ' . $timeSlot->end_time,
    //                 'duration' => $config['examDuration'],
    //                 'total_mark' => $config['totalMark'],
    //                 'passing_mark_num' => $config['passingMark'],
    //                 'passing_mark_percentage' => $config['passingPercentage'],
    //                 'is_active' => true,
    //                 'negative_mark' => $config['negativeMark'],
    //                 'remarks' => 'Auto-generated exam.',
    //                 'no_of_ques' => $config['totalQuestions'],
    //                 'exam_type' => $config['examType'],
    //             ]);

    //             // Attach selected questions to the exam
    //             $exam->questions()->attach($questionIds);

    //             Log::info('Exam created successfully', [
    //                 'examName' => $config['examName'],
    //                 'questionCount' => count($questionIds),
    //             ]);
    //         });
    //     } catch (\Exception $e) {
    //         Log::error('Error creating exam: ' . $e->getMessage(), [
    //             'config' => $config,
    //             'stackTrace' => $e->getTraceAsString(),
    //         ]);
    //     }
    // }




    // protected static function booted()
    // {
    //     static::created(function ($timeSlot) {
    //         try {
    //             $examConfigs = config('examconfig');
    //             $examService = app()->make(ExamService::class);

    //             foreach ($examConfigs as $examConfig) {
    //                 $examService->createExam($examConfig);
    //             }
    //         } catch (\Exception $e) {
    //             Log::error('Error auto-creating exams: ' . $e->getMessage(), [
    //                 'timeSlot' => $timeSlot,
    //                 'stackTrace' => $e->getTraceAsString(),
    //             ]);
    //         }
    //     });
    // }

}
