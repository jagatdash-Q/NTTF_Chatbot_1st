<?php

namespace App\Models\CoreProgrammeModels;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Calculation\Statistical\Distributions\StudentT;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class StudentPaymentTransactions extends Model
{
    use HasFactory, Notifiable, SoftDeletes, LogsActivity;
    protected $fillable = [
        'student_id',
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
        'phone',
        'udf1',
        'udf2',
        'udf3',
        'udf4',
        'udf5',
        'udf6',
        'udf7',
        'udf8',
        'udf9',
        'udf10',
        'payment_receipt_type'

    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
    public function getCreatedBy(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }
    public function getStudent(): HasOne
    {
        return $this->hasOne(Studentlead::class, 'ticket_number', 'student_id');
    }
}
