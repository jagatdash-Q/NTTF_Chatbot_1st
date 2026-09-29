<?php


namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\User;

class CoreStudentsSubjectsResultConfig extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = [
        'subject_name',
        'subject_code',
        'subject_id',
        'credit',
        's_min',
        's_max',
        'e_min',
        'e_max',
        't_min',
        't_max',
        'exam_type'



    ];
   
    public $table = 'core_students_subjects_result_configs';
     public $timestamps = false;
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
   
}
