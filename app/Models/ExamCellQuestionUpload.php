<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ExamCellQuestionUpload extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = [
        'document_name',
        'exam_cell_mem_id',
        'status',
        'document_types',
        'created_by',
        'updated_by',
        'exam_date',
        'start_time',
       
       
      
    ];
    public $table = 'legs_exam_cell_question_uploads';
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
