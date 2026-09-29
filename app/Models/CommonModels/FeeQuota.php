<?php

namespace App\Models\CommonModels;

use Illuminate\Database\Eloquent\Model;

class FeeQuota extends Model
{
    public $fillable = [
        'uuid',
        'name',
        'type',
        'center_code',
        'is_active'
    ];
}
