<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrincipalReportPdf extends Model
{
    protected $table = 'principal_report_pdfs';

    protected $fillable = [
        'center_id',
        'month_year',
        'file_name',
        'file_path',
        'saved_by',
        'saved_at',
        'is_saved',
    ];

    protected $casts = [
        'saved_at' => 'datetime',
        'is_saved' => 'boolean',
    ];
}