<?php

namespace App\Models\LegsProgrammeModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LegsStudentRequiredDocuments extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = ['document_name','description','status'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
