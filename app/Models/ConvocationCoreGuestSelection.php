<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConvocationCoreGuestSelection extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'convocation_core_guest_selections';
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
