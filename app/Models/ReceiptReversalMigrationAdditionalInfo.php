<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiptReversalMigrationAdditionalInfo extends Model
{
    protected $fillable = [
        'import_id',
        'student_payment_id',
        'reversaltransactionnumber',
        'autoreceiptnumber',
        'term',
        'feehead',
        'studentcentre',
        'feehead_id',
        'created_by',
        'updated_by',
    ];
}
