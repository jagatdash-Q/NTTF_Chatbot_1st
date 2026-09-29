<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiptMigrationAdditionalInfo extends Model
{
    protected $fillable = [
        'import_id',
        'student_payment_id',
        'created_by',
        'updated_by',
    ];
}
