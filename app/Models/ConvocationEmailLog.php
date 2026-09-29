<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConvocationEmailLog extends Model
{
    protected $fillable = [

        'convocation_process_id',

        'student_admission_id',

        'email',

        'mail_type',

        'status',

        'error_message',

        'sent_at',
    ];
}