<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndustryPartnerMouExpiryEmailLog extends Model
{
    protected $fillable = [
        'industry_partner_master_details_id',
        'is_email_sent',
        'email_sent_for',
    ];
}
