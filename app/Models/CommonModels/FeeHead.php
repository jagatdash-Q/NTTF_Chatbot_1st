<?php

namespace App\Models\CommonModels;

use Illuminate\Database\Eloquent\Model;

class FeeHead extends Model
{
    public $fillable = [
        'uuid',
        'fee_head_name',
        'center_category',
        'amount',
        'is_credit',
        'is_active',
        'created_by',
        'updated_by',
        'sort_order',
    ];
}
