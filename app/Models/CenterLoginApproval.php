<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CenterLoginApproval extends Model
{
    use HasFactory;

    protected $table = 'centerlogin_approval';

    protected $fillable = [
        'user_id',
        'nttf_odoo_employee_id',
        'date',
        'checkin_time',
        'checkout_time',
        'login_type',
        'approval_status',
        'approvedby_id',
        'approvedby_name','attendance_id'
    ];

   

  
}
