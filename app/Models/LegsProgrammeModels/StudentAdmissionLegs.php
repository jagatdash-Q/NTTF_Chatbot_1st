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

class StudentAdmissionLegs extends Model
{
    use HasFactory, Notifiable, LogsActivity;
    protected $fillable = [
        'first_name',
        'last_name',
        'name',
        'uuid',
        'email',
        'mobile',
        'centre_id',
        'age',
        'dob',
        'program_id',
        'course_id',
        'alternate_mobile',
        'student_lead_id',
        'address_id',
        'contact_details_id',
        'counseling_center_id',
        'highest_educational_qualification_id',
        'domicile_category',
        'lead_source_id',
        'referred_by',
        'referer_mobile',
        'is_active',
        'cur_address',
        'cur_school_or_college',
        'cur_education',
        'remarks',
        'status',
        'adhar_card_no',
        'gender',
        'industry_partner_id',
        'enrolement_status',
        'created_by',
        'updated_by',
        

        'stipend', 
        'stipend_amount',
        'uniform', 
        'industry_program_type',
        'canteen', 
        'insurance',
        'transport', 
        'accommodation', 
        'attendance_bonus', 
        'kaizen_rewards', 
        'any_other_details', 
        'location_of_training', 
        'date_and_time',
        'induction_rope_session', 
        'student_batch_industry',
        'student_section_industry', 
        'industry_program_name',
        'industry_program_id', 
        'centre_program_id', 
        'centre_program_name',
        'student_batch_industry_type',
        'student_section_industry_type', 
        'mail_status',
        'admission_status',
        'induction_at',
        'rope_in_at',
        'student_apaar_id',
        'batch_id',
        'batch_name',
        'token_id',
        'is_lateral',
        'academic_programme_id',

        'id_card',
        'safety_shoes',

        'middle_name','student_code', 'student_status',
        'year_month', 'batch_code','landline', 
        'caste', 'nationality', 'blood_group',
        
        // Bank Details
        'account_holder_name', 'bank_name', 'bank_branch', 'ifsc_code', 'bank_account_no',
        
        // Permanent Address
        'permanent_address_type', 'permanent_address', 'permanent_city', 'permanent_district', 
        'permanent_state', 'permanent_country', 'permanent_pincode',
        
        // Temporary Address
        'temporary_address_type', 'temporary_address', 'temporary_city', 'temporary_district', 
        'temporary_state', 'temporary_country', 'temporary_pincode',
        
        // Family Details
        'father_name', 'father_mobile_no', 'father_email_id', 'father_occupation',
        'mother_name', 'mother_mobile_no', 'mother_email_id', 'mother_occupation',
        'guardian_name', 'guardian_mobile_no', 'guardian_email_id', 'guardian_occupation','is_primary',
        
        // Additional Details
        'highest_qualification',  'name_as_per_aadhar',
        'user_id','mobilized_by','student_whatsapp_no','2_yr_iti','trade','passing_year','permanent_taluk_post','student_sourcing_from',
        'entrance_test_result','entrance_test_date','reporting_date',

        'location','reporting_person_email','reporting_person_alternative_phone','reporting_person_phone','reporting_person_name',
        'billing_plant_id','billing_plant_name',

        




    ];
    public $table = 'student_admission_legs';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

   
}