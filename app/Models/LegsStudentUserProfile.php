<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class LegsStudentUserProfile extends Model
{
    use HasFactory, LogsActivity;
   protected $table = 'legs_student_user_profile';
   protected $fillable = [
        'student_id',
        'image_path',
        'original_name',
        'mime_type',
        'size'
    ];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}