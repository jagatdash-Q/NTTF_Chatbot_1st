<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LegsAwardOfClass extends Model
{
     protected $fillable = [
        'program_type', 
        'min_cgpa', 
        'max_cgpa', 
        'classification', 
        'result_code'
    ];
    
    protected $casts = [
        'min_cgpa' => 'decimal:1',
        'max_cgpa' => 'decimal:1'
    ];
}
