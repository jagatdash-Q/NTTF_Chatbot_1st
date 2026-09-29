<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\User;

class CoreStudentResults extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = [
        'core_student_id', 'core_subject_master_id','core_student_token_id',
        's_mark', 'e_mark', 't_mark', 'grade', 'gp','gr_total','result','sgpa','cgpa',
        'created_by','updated_by',
        'semester_id','core_batch_id','core_batch_name',
        'program_id','program_name',
        'stream_id','stream_name',
        'center_id','center_name',
        'core_zone_id','exam_type'
    ];
   
    public $table = 'core_student_results';
     public $timestamps = false;
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
   
}
