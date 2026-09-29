<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HolidaysList extends Model
{
    use HasFactory;

   protected $table = 'holidays_list';

    protected $fillable = [
        'odoo_id',
        'name',
        'date_from',
        'date_to',
        'company_id',
        'company_name',
        'calendar_id',
    ];

  
}