<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NttfRepresentative extends Model
{
    use HasFactory;

    protected $fillable = [
        'review_id',
        'name',
        'custom_id',
    ];
}
