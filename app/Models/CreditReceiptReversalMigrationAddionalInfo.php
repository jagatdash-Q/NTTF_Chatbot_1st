<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditReceiptReversalMigrationAddionalInfo extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'import_id',
        'student_payment_id',
        'reversaltransactionnumber',
        'term',
        'feehead',
        'studentcentre',
        'creditvouchernumber',
        'autoreceiptnumber',
        'feehead_id',
        'created_by',
        'updated_by',
    ];
}
