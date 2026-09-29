<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\CoreProgrammeModels\StudentAdmission;
use App\Models\User;

class BiometricLog extends Model
{
    use HasFactory;
    public $table = "biometric_info";
    protected $fillable = [
        'user_id',
        'student_lead_id',
        'student_admission_id',
        'core_student_code',
        'punching_code',
        'punch_time',
        'serial_number',
        'location',
        'device_id',
        'verification_type',
        'metadata',
    ];

    public function student()
    {
        return $this->belongsTo(StudentAdmission::class, 'student_admission_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
