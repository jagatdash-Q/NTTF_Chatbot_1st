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

class LegsBatchStudentCurrentSemester extends Model
{
    use HasFactory, Notifiable, LogsActivity;
    public $table = 'legs_batch_student_current_semesters';
    protected $fillable = [
        'batch_id',
        'student_id',
        'first_name',
        'last_name',
        'uuid',
        'token_id',
        'current_semester',
        'total_semester',
        'program_id',
        'industry_id',
        'created_by',
        'updated_by',
        'billing_plant_id',
        'billing_plant_name'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
   
    
}
