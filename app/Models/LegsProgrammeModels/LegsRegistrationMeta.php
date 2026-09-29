<?php



namespace App\Models\LegsProgrammeModels;

use App\Models\CommonModels\Address;
use App\Models\CommonModels\HighestEducationalQualification;
use App\Models\CommonModels\LeadMaster;
use App\Models\CommonModels\MediaManager;
use App\Models\CommonModels\PaymentMode;
use App\Models\CommonModels\QuotaSystem;
use App\Models\CommonModels\StudentEducationDetails;
use App\Models\CommonModels\StudentFamily;
use App\Models\CommonModels\TestTimeSlot;
use App\Models\CommonModels\UserIdentification;
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

class LegsRegistrationMeta extends Model
{
    use HasFactory, Notifiable, LogsActivity;
    protected $fillable = [
       'is_tml','co_name','batch_id','student_id',



        'beg_date', 'end_date', 'reason_for_action', 
        'position', 'employee_group', 'employee_subgroup', 
        'cost_center', 'payroll_area', 'job_key',
        'bg_designation', 'known_as', 'marital_status', 
        'country_key', 'work_schedule_rule',
        'time_mgm_status', 'pay_scale_group', 'basic_amount',
        'wage_type_it_0014', 'wage_type_amount_it_0014',
        'education_training', 'educ_est_inst_location', 
        'faculty', 'branch_of_study_1', 'branch_of_study_2',
        'z5_date', 'z7_date', 'accommodation_type', 
        'rent_amount', 'hra_tax_exempt', 'conveyance_type',
        'pf_flag', 'temp_cert_esi_number', 'esi_dispensa',
        'contributing_to_lwf', 'ptax_eligibility', 
        'punch_location', 'business_area', 'contract',
        'pan', 'uan', 'pay_scale_level', 'place_of_birth',
        'time_recording_id', 'tml_smart_card_id', 
        'ou_location', 'last_separation_date'
     
        




    ];
    public $table = 'legs_registration_metas';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

   
}