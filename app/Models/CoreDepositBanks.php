<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\CommonModels\Centres as Center;
use App\Models\CommonModels\PaymentMode;

class CoreDepositBanks extends Model
{
    protected $fillable = [
        'center_id',
        'center_code',
        'bank_name',
        'series',
        'instrument_type',
        'payment_method_id',
        'created_by',
        'updated_by',
    ];

      public function center()
    {
        return $this->belongsTo(Center::class);
    }

    public function paymentMode()
    {
        return $this->belongsTo(PaymentMode::class, 'payment_method_id');
    }
}
