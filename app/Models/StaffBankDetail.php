<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;

class StaffBankDetail extends Model
{
    use HasFactory, LogsActivity;

    protected $table = 'staff_bankdetails';

    protected $fillable = [
        'employee_id',
        'bank_name',
        'bank_branch',
        'account_type',
        'payment_type',
        'ifsc_code',
        'account_username',
        'created_by',
        'updated_by',
    ];
 public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
 
}
