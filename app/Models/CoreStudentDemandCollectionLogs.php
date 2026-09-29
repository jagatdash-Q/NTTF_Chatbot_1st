<?php

namespace App\Models;

use App\Models\CommonModels\PaymentMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CoreStudentDemandCollectionLogs extends Model
{
    protected $fillable = [
        'demand_id',
        'core_student_fee_plan_detail_log_id',
        'collection_reference_no',
        'uuid',
        'student_lead_id',
        'student_code',
        'student_admission_id',
        'created_by',
        'total_amount',
        'remaining_amount',
        'total_collected_amount',
        'total_semester_fee_head_amount',
        'payment_date',
        'collection_type',
        'payment_mode',
        'reciept_series',
        'bank_name',
        'branch_name',
        'instrument_identifier',
        'instrument_date',
        'remarks',
        'deposit_bank',
        'is_paid_from_advance',
        'is_reverted_transaction',
        'is_reverted',
        'semester'
    ];

    public function getCollectionLogs(): HasMany
    {
        return $this->hasMany(CoreStudentDemandCollectionFeeHeadWiseLogs::class, 'core_student_demand_collection_logs_id', 'id');
    }

    public function getDemandDetails(): HasOne
    {
        return $this->HasOne(CoreDemandGenerateLogs::class, 'id', 'demand_id');
    }

    public function getPaymentMethod(): HasOne
    {
        return $this->hasOne(PaymentMode::class, 'id', 'payment_mode');
    }
}
