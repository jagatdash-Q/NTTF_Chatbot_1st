<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\UuidV7;
class EmployeeLevel extends Model
{
    use HasFactory;

    protected $table = 'employee_levels';

    protected $fillable = [
        'uuid',
        'employee_id',
        'employee_name',
        'employee_email',
        'subject_id',
        'level',
        'created_by',
        'updated_by',
    ];
    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->uuid = new UuidV7();
        });
    }
}
