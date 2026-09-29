<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HighestQualificationMapping extends Model
{
    use HasFactory;

    protected $table = 'highestqualification_mapping';

    protected $fillable = [
        'program_id',
        'program_name',
        'stream_id',
        'stream_name',
        'qualification_id',
        'qualification_name',
        'created_by',
        'updated_by',
    ];

  
}