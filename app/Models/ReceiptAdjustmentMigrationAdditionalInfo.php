<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiptAdjustmentMigrationAdditionalInfo extends Model
{
    protected $fillable = [
        'import_id',
        'adjustment_id',
        'student_payment_id',
        'term',
        'studentcentre',
        'credittype',
        'created_by',
        'updated_by',
    ];
}
