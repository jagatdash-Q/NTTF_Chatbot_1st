<?php

namespace App\Models;

use App\Models\CommonModels\StudentPayment;
use App\Models\CoreProgrammeModels\StudentAdmission;
use App\Models\CoreProgrammeModels\Studentlead;
use Illuminate\Database\Eloquent\Model;

class CoreCreditToStudent extends Model
{
    protected $fillable = [
        'student_lead_id',
        'created_by',
        'updated_by',
        'uuid',
        'reciept_no',
        'amount',
        'is_reverted',
        'is_reverted_transaction',
        'remarks',
        'pending_amount',
        'collected_amount',
        'created_with',
        'term',
        'import_id',
        'receipt_series',
        'migrated_on',
        'semester'
    ];

    public function feeHeadDetail()
    {
        return $this->hasMany(CoreCreditToStudentFeeheadWiseLogs::class, 'core_credit_to_students_id', 'id');
    }

    public function get_creator_details()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function getStudentDetails()
    {
        return $this->hasOne(Studentlead::class, 'id', 'student_lead_id');
    }

    public function getCompleteAdmissionDetails()
    {
        return $this->hasOne(StudentAdmission::class, 'student_lead_id', 'student_lead_id')->with(['getQuota', 'getHighestEducational', 'getLead', 'getUserIdentification', 'getApplicantPhoto', 'getFamily', 'getEducationDetails', 'getAddress', 'getCenterDetails', 'getProgramDetails', 'getStreamDetails']);
    }
}
