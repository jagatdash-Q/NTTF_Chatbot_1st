<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiptCreditMigrationAdditionalInfo extends Model
{
    protected $fillable = [
        'import_id',
        'student_payment_id',
        'credit_voucher_number',
        'collection_date',
        'credit_date',
        'credit_feehead_name',
        'credit_feehead_id',
        'created_by',
        'updated_by',
    ];
}
