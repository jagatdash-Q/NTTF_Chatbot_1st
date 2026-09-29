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

class LegsReportingPersonsDetails extends Model
{
    use HasFactory, Notifiable, LogsActivity;
    protected $fillable = [
        'reporting_person_name',
        'designation',
        'phone',
        'alternative_phone',
        'email',
        'address',
        'status',
        'created_by',
        'updated_by',
        'batch_id',
        'industry_partner_id',
    ];
    public $table = 'legs_reporting_persons_details';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

   
}