<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrincipalReportSnapshot extends Model
{
    protected $table = 'principal_report_snapshots';

    protected $fillable = [
        'principal_report_pdf_id',
        'section_key',
        'section_data',
        'section_version',
    ];

    protected $casts = [
        'section_data' => 'array',
        'section_version' => 'integer',
    ];
}