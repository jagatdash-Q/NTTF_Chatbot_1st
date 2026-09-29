<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CalenderProgramRegistration extends Model
{
    protected $fillable = [
        'uuid',
        'customer_name',
        'customer_location',
        'contact_person_name',
        'contact_person_mobile',
        'contact_person_email',
        'calendar_program_name',
        'month',
        'year',
        'no_of_nomination',
        'total_payment',
        'remarks',
        'payment_status',
        'created_by',
        'updated_by',
    ];
}
