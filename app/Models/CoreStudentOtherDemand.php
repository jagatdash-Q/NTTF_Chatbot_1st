<?php

namespace App\Models;

use App\Models\CoreProgrammeModels\StudentAdmission;
use App\Models\CoreProgrammeModels\Studentlead;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CoreStudentOtherDemand extends Model
{
    protected $fillable = [
        'other_demand_code',
        'uuid',
        'student_lead_id',
        'student_code',
        'total_times_updated',
        'total_amount',
        'collection_date',
        'current_core_student_other_demand_active',
        'created_by',
        'updated_by',
        'remarks',
        'other_demand_bulk_generate_log_id',
        'semester',
        'academic_year_id',
        'center_id',
        'stream_id',
        'program_type',
        'is_reverted',
        'demand_reverse_remarks',
        'created_with',
        'import_id',
        'migrated_on',
        'demand_reverse_date',
        'demand_type',
    ];

    public function getStudentDetails(): HasOne
    {
        return $this->hasOne(StudentAdmission::class, 'student_lead_id', 'student_lead_id');
    }

    public function getStudentLeadDetails(): HasOne
    {
        return $this->hasOne(Studentlead::class, 'id', 'student_lead_id');
    }

    public function getCreatorDetails(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function getUpdatorDetails(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function getCurrentActiveFeeHeadList(): HasOne
    {
        return $this->hasOne(CoreStudentOtherDemandActiveDetails::class, 'id', 'current_core_student_other_demand_active')->with('getCurrentFeeHeadList');
    }

    public function getCollectionWithLogDetails(): HasMany{
        return $this->hasMany(CoreOtherDemandCollectionLogWithBankDetails::class, 'other_demand_id', 'id')->with('getReceipts');
    }

}
