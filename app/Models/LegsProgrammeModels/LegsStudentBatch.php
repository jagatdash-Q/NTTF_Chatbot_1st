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
use App\Models\StudentDeploymentDetail;

class LegsStudentBatch extends Model
{
    use HasFactory, Notifiable, LogsActivity;
    public $table = 'legs_student_batches';
    protected $fillable = [
        'industry_partner_id',
        'program_id',
        'qualification',
        'number_of_students',
        'start_date',
        'end_date',
        'year_span',
        'batch_month',
        'batch_year',
        'stipend',
        'batch_name',
        'uuid',
        'industry_partner_name',
        'program_name',
        'stipend_amount',
        'created_by',
        'updated_by',
        'batch_code',
        'batch_coordinator_name',
        'legs_zone_id',
        'semester_no',
        'current_semester',

        
        'rope_in_time_table',
        'list_of_soft_skill_trainer',
        'induction_time_table',
        'welcome_kit',
        'arr',
        'fundamental_skill_training',
        'tml_induction',
        'deployement',
        'rope_in_time_start_date',
        'rope_in_time_end_date',
        'rope_in_time_file',
        'list_of_soft_skill_file',
        'induction_time_table_start_date',
        'induction_time_table_end_date',
        'induction_time_table_file',
        'trainer_name',
        'trainer_id',
        'other_qualification_name',
        'legs_employee_id',
        'employee_type','lms','program_years','batch_durations',
        'billing_plant_name','billing_plant_id','ncvet_course_deposit_fee'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
    public function studentDeployments()
        {
            return $this->hasMany(StudentDeploymentDetail::class, 'batch_id');
        }

    // public function company()
    // {
    //     return $this->belongsTo(Company::class);
    // }

    // public function program()
    // {
    //     return $this->belongsTo(Program::class);
    // }
    protected $casts = [
        'batch_durations' => 'array',
    ];
    
}
