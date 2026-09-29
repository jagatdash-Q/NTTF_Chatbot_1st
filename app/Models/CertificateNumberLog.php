<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CertificateNumberLog extends Model
{
    protected $table = 'certificate_number_logs';

    protected $fillable = [
        'old_cert_no',
        'new_cert_no',
        'batch_id',
        'student_code',
        'changed_by',
    ];
}