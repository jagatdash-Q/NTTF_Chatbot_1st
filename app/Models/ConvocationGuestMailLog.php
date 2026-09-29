<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConvocationGuestMailLog extends Model
{
    protected $table = 'convocation_guest_mail_log';

    protected $fillable = [
        'convocation_process_id',
        'student_admission_id',
        'email',
        'status',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];
}