<?php

namespace App\Models\CommonModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\CommonModels\Address;
use App\Models\CommonModels\ContactDetails;
use App\Models\CenterBranches;
use App\Models\AcademicSectionModels\CollegeAcademicProgrammeStreams;

class Centres extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;


    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }

    protected $table = 'centres';

    // Define the fillable fields for mass assignment
    protected $fillable = [
        'CentreCode',
        'uuid',
        'ZoneCode',
        'Zone_id',
        'State_id',
        'Center_category_id',
        'Corporate_id',
        'CentreType',
        'Address_id',
        'Contact_details_id',
        'CentreName',
        'CentreShortName',
        'certificate_name',
        'CentreStatus',
        'CreatedOn',
        'created_by',
        'modified_on',
        'modified_by',
        'legs_zone_id',
        'registration_applicable',
        'centre_category_name',
        'centre_type_name',
        'centre_programme_id',
        'centre_programme_name',
        'centre_address',
        'centre_principal_name',
        'centre_principal_contact',
        'centre_principal_email',
        'centre_principal_designation',
        'company_branch_name',
        'company_division_name'
    ];

    // Define relationships
    public function zone()
    {
        return $this->belongsTo(Zones::class, 'Zone_id');
    }

    public function address()
    {
        return $this->belongsTo(Address::class, 'Address_id');
    }

    public function contact()
    {
        return $this->belongsTo(ContactDetails::class, 'Contact_details_id');
    }

    public function centreCategory()
    {
        return $this->belongsTo(CentreCategoryTypes::class, 'Center_category_id');
    }
    public function streams()
    {
        return $this->belongsToMany(CollegeAcademicProgrammeStreams::class, 'center_wise_course_with_category_types', 'center_id', 'stream_id');
    }
    public function branches()
    {
        return $this->hasMany(CenterBranches::class, 'centre_id');
    }
}
