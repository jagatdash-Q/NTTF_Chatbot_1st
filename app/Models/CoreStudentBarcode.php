<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\UuidV7;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CoreStudentBarcode extends Model
{
    use HasFactory;

    protected $table = 'core_student_barcodes';

    protected $fillable = [
        'uuid',
        'student_code',
        'student_name',
        'core_student_id',
        'student_barcode',
        'student_barcode_file',
        'created_by',
        'updated_by',
        'status' ,  
        'program_id',
        'stream_id',
        'center_id',
        'semester_no',
        'core_zone_id',
        'core_batch_id',
        'semester_id',
        'subject_id',
        'academic_year_span'
    ];

    /**
     *  Setup model event hooks
     */
    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->uuid = new UuidV7();
        });
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}