<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegsStudentCertificate extends Model
{
    use HasFactory;

    protected $table = 'legs_student_certificate';

    protected $primaryKey = 'cert_no';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'cert_no',
        'new_cert_no',
        'certificate_date',
        'student_name',
        'student_code',
        'dob',
        'billing_plant_id',
        'program_name',
        'award_of_class',
        'year_of_completion',
        'study_center',
        'photo',
        'qr_link',
        'additional_info',
    ];

    protected $casts = [
        'certificate_date' => 'date',
        'dob' => 'date',
        'year_of_completion' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function getDisplayCertNoAttribute()
    {
        return $this->new_cert_no ?? $this->cert_no;
    }
}