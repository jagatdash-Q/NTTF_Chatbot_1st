<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConvocationCoreDobConfirmation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'convocation_core_dob_confirmations';
    protected $guarded = [];

    public function process()
    {
        return $this->belongsTo(ConvocationCoreProcess::class, 'convocation_process_id');
    }

    public function student()
    {
        return $this->belongsTo(\App\Models\CoreProgrammeModels\StudentAdmission::class, 'student_admission_id');
    }
}
