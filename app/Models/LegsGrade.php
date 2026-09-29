<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class LegsGrade extends Model
{
   use HasFactory, LogsActivity;
   protected $table = 'legs_grades';
   protected $fillable = ['name', 'point', 'remarks'];

   protected $casts = [
      'point' => 'decimal:1'
   ];

   public function boundaries()
   {
      return $this->hasMany(LegsGradeBoundary::class, 'grade_id');
   }

   public function getActivitylogOptions(): LogOptions
   {
      return LogOptions::defaults();
   }

    /**
     * Get boundary for specific test type
     */
    public function getBoundaryForTestType($testType)
    {
        return $this->boundaries()->where('test_type', $testType)->first();
    }

    /**
     * Scope to get grades with their boundaries
     */
    public function scopeWithBoundaries($query)
    {
        return $query->with('boundaries');
    }
}
