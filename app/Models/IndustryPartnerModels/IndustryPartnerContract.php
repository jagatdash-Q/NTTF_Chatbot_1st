<?php

namespace App\Models\IndustryPartnerModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class IndustryPartnerContract extends Model
{
    use HasFactory, LogsActivity;
    protected $table = 'IndustryPartnerContract';

    protected $fillable = [
        'IP_ContractCode',
        'IndustryPartnerMasterDetails_id',
        'Centre_Code',
        'IP_MoURefNo',
        'IP_ContractSignDate',
        'IP_ContractValidity',
        'IP_CommencementDate',
        'IP_PONo',
        'IP_PlantName',
        'IP_PlantLocation',
        'OJT_ProgramName',
        'TrainLocation_RopeIN',
        'TrainLocation_WTS',
        'TrainLocation_OJT',
        'TrainingHallCapacity',
        'IP_InfraDetails',
        'TrainQualification',
        'TrainGenderRatio',
        'TrainAgeLimit',
        'Batch_Size',
        'FutureBatchReq',
        'TrainingSourcing',
        'TrainingSourcingLocation',
        'TrainingDay',
        'SyllabusStatus',
        'Uniform',
        'SafetyShoes',
        'Insurance',
        'Stipend',
        'StipendAmount',
        'Accommodation',
        'Canteen',
        'Canteen_Details',
        'Food_Subsidised',
        'Transport',
        'AttendanceBonus',
        'Kaizen_5S_Rewards',
        'IP_Contract_RefDoc',
        'NQF_OUT_QOSPrograms',
        'PurchaseOrderNo',
        'TrainingCompletionDate',
        'LocationofTraineeSourcing',
        'StipulationStatus',
        'FutureBatchesRequired',
        'Ageoftrainee',
        'genderoftrainee',
        'TheorydayagreedwithCluster',
        'ContractCommencementDate',
        'MonitoringFrequency',
        'TrainingSourcingBy',
        'TrainingLocation',
        'BatchSize',
        'BatchQualification',
        'Created_On',
        'Created_By',
        'Modified_On',
        'Modified_By',
        'male_trainee_nos',
        'female_trainee_nos',
        'male_trainee_age',
        'female_trainee_age',
        'TrainLocation_OJT',
        'TrainLocation_WTS',
        'TrainLocation_RopeIN',
        'NQF_OUT_QOSPrograms_id',
        'program_id',
        'total_no_trainee',
        'future_batch_details',
        'future_batch_start_size_no',
        'future_batch_end_size_no',
        'frequency_of_mc_meetings',
        'additional_conditions',

        // Expenses
        'ncv_course_deposit',
        'course_material_expenses',
        'stationary_expenses',
        'welcome_kit_cost',

        // Employee and Turnover Info
        'no_of_employees_regular',
        'no_of_employees_contractual',
        'no_of_employees_apprentice',
        'annual_turnover',

        // Facilities
        'canteen_facility', // Trainees/Trainers/Both
        'transport_facility', // Trainees/Trainers/Both
        'accommodation_for_trainees',
        'attendance_bonus',
        'insurance_provider',

        // Syllabus and Other Info
        'syllabus_finalization',
        'specific_info',
        'nttf_program_coordinator_name',
        'company_program_coordinator_name',

        // Booleans for Facilities
        'canteen',
        'insurance',
        'transport',
        'accommodation',
        'attendance_bonus',

        // Gender-Specific Options
        'is_male',
        'is_female',

        // Plant Details
        'plant_unionised',
        'total_turn_over_of_plant',
        'plant_total_work_force',

        'gender_type',
        'payrole_manage_by',
        'program_title',

        'nttf_program_coordinator_phone',
        'company_program_coordinator_phone',
        'facility_provided',

        
        'billing_plant_id',
        'billing_plant_name',

    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
