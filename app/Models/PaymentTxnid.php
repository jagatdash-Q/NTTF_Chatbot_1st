<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTxnid extends Model
{
    use HasFactory;

    protected $table = 'payment_txnids';

    protected $fillable = [
        'txnid',
        'payment_type',
    ];
}
