<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoreStudentRequiredDocuments extends Model
{
    protected $fillable = [
        'document_name',
        'description',
        'status',
    ];
}
