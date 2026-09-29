<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeploymentArea extends Model
{
    protected $fillable = [
        'serial_no',
        'tml_ticket_sap_number',
        'aadhar_no',
        'nttf_roll_no',
        'complete_name',
        'gender',
        'entry_level_qualification',
        'date_of_birth',
        'age_in_years',
        'date_of_joining_diploma_program',
        'date_of_passing_diploma_program',
        'diploma_cgpa',
        'batch_no',
        'batch_ranking',
        'location_tml',
        'date_of_deployment',
        'deployed_plant_ojt_01',
        'ojt_skill_1_1',
        'ojt_skill_1_2',
        'deployed_plant_ojt_02',
        'ojt_skill_2_1',
        'ojt_skill_2_2',
        'deployed_plant_ojt_03',
        'ojt_skill_3_1',
        'ojt_skill_3_2',
        'native_state',
        'home_town',
        'occupation_father_mother',
        'iti_marks_or_plus2_marks',
        'iti_trade_or_plus2_stream',
        'iti_plus2_institute_name',
        'iti_plus2_passing_year',
        'total_experience_years',
        'email',
        'contact_number',
        'alternate_contact_number',
        'batch','industry_partner','zone','qualification'
    ];
}
