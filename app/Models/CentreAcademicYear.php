<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\UuidV7;
class CentreAcademicYear extends Model
{
    use HasFactory;
    protected $fillable = [
        'uuid',
        'centre_id',
        'centre_name',
        'core_batch_id',
        'core_batch_name',
        'academic_year',
        'academic_month',
        'status',
        'created_by',
        'updated_by'
    ];
    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->uuid = new UuidV7();
        });
    }
}
