<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NoncoreWorkingSchedule extends Model
{
    use HasFactory;

    protected $table = 'noncore_working_schedule';

    protected $fillable = [
        'odoo_id',
        'calendar_id',
        'calendar_name',
        'month',
        'year',
        'days',
        'start_date',
        'end_date',
        'display_name',
    ];
}