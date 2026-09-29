<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExternalTraining extends Model
{
    use HasFactory;

    protected $table = 'external_training';

    protected $fillable = [
        'program_name',
    ];
}