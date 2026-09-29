<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\CoreProgrammeModels\StudentAdmission;

class StudentCertificate extends Model
{
    use HasFactory;

    protected $table = 'student_certificates';

    protected $fillable = [
        'student_code',
        'student_admission_id',
        'temp_cert_no',
        'certificate_no',
        'prov_running_no',
        'stream_code',
        'running_digit',
        'certificate_year',
        'qr_code_link',
        'pass_status',
        'classification',
        'before_convocation',
        'center_id',
        'program_id',
        'stream_id',
        'academic_year_id',
        'batch_name',
        'exam_results',
    ];

    protected $casts = [
        'exam_results' => 'array',
        'certificate_year' => 'integer',
    ];

    /**
     * Relationship with StudentAdmission
     */
    public function studentAdmission()
    {
        return $this->belongsTo(StudentAdmission::class, 'student_admission_id');
    }

    /**
     * Relationship with Center
     */
    public function center()
    {
        return $this->belongsTo(\App\Models\CommonModels\Centres::class, 'center_id');
    }

    /**
     * Relationship with Program
     */
    public function program()
    {
        return $this->belongsTo(\App\Models\AcademicSectionModels\CollegeAcademicProgrammes::class, 'program_id');
    }

    /**
     * Relationship with Stream
     */
    public function stream()
    {
        return $this->belongsTo(\App\Models\AcademicSectionModels\CollegeAcademicProgrammeStreams::class, 'stream_id');
    }

    /**
     * Scope to get certificates by batch
     */
    public function scopeByBatch($query, $batchName)
    {
        return $query->where('batch_name', $batchName);
    }

    /**
     * Scope to filter by pass status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('pass_status', $status);
    }

    /**
     * Scope to get certificates for a specific academic year
     */
    public function scopeByAcademicYear($query, $academicYearId)
    {
        return $query->where('academic_year_id', $academicYearId);
    }

    /**
     * Get the next running digit for certificate number generation
     */
    public static function getNextRunningDigit($batchName, $streamCode, $year)
    {
        $lastCert = self::where('batch_name', $batchName)
            ->where('stream_code', $streamCode)
            ->where('certificate_year', $year)
            ->where('pass_status', 'pass')
            ->orderBy('running_digit', 'desc')
            ->first();

        return $lastCert ? $lastCert->running_digit + 1 : 1;
    }
}
