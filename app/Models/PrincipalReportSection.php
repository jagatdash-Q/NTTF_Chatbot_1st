<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrincipalReportSection extends Model
{
    protected $fillable = [
        'section_key',
        'section_name',
        'display_order',
        'is_visible',
        'updated_by',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}