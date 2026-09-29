<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalenderProgramPaymentDetails extends Model
{
    protected $fillable = [
        'uuid',
        'calender_program_registration_id',
        'key',
        'hash',
        'txnid',
        'name',
        'email',
        'amount',
        'productinfo',
        'surl',
        'furl',
        'service_provider',
        'status',
        'response',
        'created_by',
        'updated_by','phone', 'udf1', 'udf2', 'udf3', 'udf4',
                'udf5',
                'udf6',
                'udf7',
                'udf8',
                'udf9',
                'udf10',
    ];
}
