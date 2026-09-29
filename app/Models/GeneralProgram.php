<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralProgram extends Model
{
    use HasFactory;

    protected $table = 'general_program';

    protected $fillable = [
        'general_program',
    ];
}