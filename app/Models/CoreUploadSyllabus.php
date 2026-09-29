<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CoreUploadSyllabus extends Model
{
  

    protected $table = 'coreupload_syllabi';

    protected $fillable = [
        'program_type',
        'year_month',
        'stream_id',
        'semester_id',
        'subject_id',
        'file_name',
        'file_path',
        'file_type',
        'uploaded_by',
    ];
}