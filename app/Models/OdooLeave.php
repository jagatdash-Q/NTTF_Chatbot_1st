<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OdooLeave extends Model
{
    protected $fillable = [
        'odoo_leave_id',
        'employee_odoo_id',
        'employee_name',
        'leave_type_odoo_id',
        'leave_type_name',
        'leave_reason',
        'request_date_from',
        'request_date_to',
        'number_of_days',
        'state',
        'raw_payload'
    ];
}
