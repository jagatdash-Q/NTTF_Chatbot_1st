<?php

namespace App\Models\LegsProgrammeModels;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class LegsBatchStudentRopeInDetails extends Model
{
    use HasFactory, Notifiable, LogsActivity;
    public $table = 'legs_batch_student_rope_in_details';
    protected $fillable = [
    'batch_id',
    'student_full_name',
    'student_id',
    'start_date',
    'end_date',
    'department_name',
    'department_id',
    'area_name',
    'area_id',
    'created_by',
    'updated_by',
    'first_name',
    'last_name',
    'middle_name',
    'uuid',
    'token_id'
       
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
   
    
}
