<?php

namespace App\Models;

use App\Models\CommonModels\FeeHead;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CoreFeePlanSemesterWiseDetails extends Model
{

    protected $fillable = [
        'core_fee_plan_id',
        'semester',
        'fee_head_id',
        'total_amount',
        'created_by',
        'updated_by',
        'payment_date'
    ];

    public function getFeeHeadDetails(): HasOne
    {
        return $this->hasOne(FeeHead::class, 'id', 'fee_head_id');
    }
}
