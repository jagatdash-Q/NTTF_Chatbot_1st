<?php

namespace App\Models\LegsProgrammeModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class LegsStudentRemarks extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = ['student_id', 'remarks', 'current_date', 'created_by', 'updated_by'];
    public $table = 'legs_student_remarks';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();

    }
}
