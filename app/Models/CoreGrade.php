<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;

class CoreGrade extends Model
{
    use HasFactory, LogsActivity;
    protected $table = 'core_grades';
    protected $fillable = ['name', 'point', 'qualitative_assessment'];

    protected $casts = [
        'point' => 'decimal:1'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
