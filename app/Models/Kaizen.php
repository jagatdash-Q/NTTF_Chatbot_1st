<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Kaizen extends Model
{
    use HasFactory;


    protected $fillable = [
        'industry_partner_id',
        'batch',
        'program_id',
        'legs_zone_id',
        'semester_id',
        'trainee_name',
        'suggestion_date',
        'area_of_kaizen',
        'kaizen_number',
        'kaizen_statement',
        'nttf_coordinator_name',
        'nttf_coordinator_designation',
        'pi_supervisor_name',
        'pi_supervisor_designation',
        'in_depth_study',
        'study_date',
        'is_recommended',
        'recommendation_date',
        'is_accepted',
        'target_completion',
        'implementation_details',
        'tangible_benefit',
        'intangible_benefit',
        'reward',
        'total_kaizen_by_trainee',
        'total_kaizen_at_partner',
        'total_kaizen_implemented',
        'kaizen_yet_to_implement',
        'before_image',
        'after_image',
        'supporting_documents'

    ];
}
