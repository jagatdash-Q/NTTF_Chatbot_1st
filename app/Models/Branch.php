<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = [
        'branch_name',
        'program_id','program_type', 'highest_qualification_id', 'qualification_name'
    ];
}