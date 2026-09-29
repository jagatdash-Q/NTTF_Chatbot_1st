<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrincipalReportFeeTarget extends Model
{
    protected $fillable = [
        'center_id',
        'month_year',
        'target_amount',
        'created_by',
        'updated_by',
    ];
}