<?php

namespace App\Models\CommonModels;

use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    public $fillable = [
        'name',
        'is_active'
    ];
}
