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

class PlacementStudents extends Model
{
    use HasFactory, Notifiable, LogsActivity;
    public $table = 'placement_students';
    protected $fillable = [
        'sl_no',
        'uuid',
        'student_id',
        'batch_id',
        'rank',
        'tml_p_no',
        'aadhar_no',
        'nttf_roll_no',
        'complete_name',
        'gender',
        'entry_level_qualification',
        'date_of_birth',
        'age_in_years',
        'date_of_joining_diploma',
        'date_of_passing_diploma',
        'diploma_cgpa',
        'batch_no',
        'locations_tml',
        'on_the_job_skill_1',
        'on_the_job_skill_2',
        'on_the_job_skill_3',
        'native_state',
        'iti_trade_or_plus2_stream',
        'email',
        'contact_number',
        'placement_mapping_1',
        'placement_mapping_2',
        'placement_mapping_3',
        'tooken_id',
        'verified',
        'verified_by_coordinator_name',
        'verified_date',
        'upload_resume', // Only for verified trainees
        'rope_out_programme',
        'interviewing_skills',
        'trainees_absorbed',
        'student_status',
        'status_other_reason',
        'interview_date',
        'interview_time',
        'interview_venue',
        'trainees_attending_interview',
        'job_role',
        'potential_ctc',
        'trainees_attended_selection',
        'selection_status',
        'on_hold_reason',
        'ctc_offered',
        'employer_address',
        'employer_city',
        'employer_district',
        'employer_state',
        'employer_contact_no',
        'appointment_order_upload',
        'created_by',
        'updated_by',
        'status', // Active, Inactive, Pending

    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
  
    
}
