<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentNominee extends Model
{
    protected $table = 'student_nominees';

    protected $fillable = [
        'student_lead_id',
        'nominee_type',
        'name',
        'dob',
        'created_by',
        'updated_by',
    ];

  
}
