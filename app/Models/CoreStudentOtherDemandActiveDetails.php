<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CoreStudentOtherDemandActiveDetails extends Model
{
    protected $fillable = [
        'core_student_other_demand_id',
        'created_with',
        'migrated_on',
        'student_lead_id',
        'semester',
        'demand_reverse_date',
        'is_reverted',
        'demand_reverse_remarks',
        'demand_type'
    ];

    public function getCurrentFeeHeadList(): HasMany
    {
        return $this->HasMany(CoreStudentOtherDemandFeeHeadWiseDetails::class, 'core_student_other_demand_active_details_id', 'id')->with('collections');
    }

    public function getDemandDetails(){
        return $this->hasOne(CoreStudentOtherDemand::class, 'id', 'core_student_other_demand_id');
    }
}
