<?php

namespace App\Models;

use App\Models\CommonModels\PaymentMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CoreOtherDemandCollectionLogWithBankDetails extends Model
{
    protected $fillable = [
        'collected_by',
        'other_demand_id',
        'student_lead_id',
        'reciept_no',
        'payment_method_id',
        'bank_id',
        'branch_name',
        'instrument_date',
        'instrument_identifier',
        'payment_centre',
        'deposit_bank',
        'receipt_series',
        'fee_paid',
        'remark',
        'total_reciept_download_times',
        'is_reverted',
        'is_reverted_transaction',
        'is_paid_from_advance',
        'is_direct_collection',
        'created_with',
        'migrated_on',
        'semester'
    ];

    public function getPaymentDetails(): HasOne
    {
        return $this->hasOne(PaymentMode::class, 'id', 'payment_method_id');
    }

    public function getReceipts():HasMany {
        return $this->hasMany(CollectOtherStudentDemand::class, 'collection_reference_no', 'reciept_no');
    }

    public function getDemandDetails() : HasOne{
        return $this->hasOne(CoreStudentOtherDemand::class, 'id', 'other_demand_id');
    }
}
