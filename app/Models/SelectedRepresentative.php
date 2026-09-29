<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SelectedRepresentative extends Model
{
    use HasFactory;

    protected $fillable = [
        'review_id',
        'name',
        'custom_id',
    ];
}
