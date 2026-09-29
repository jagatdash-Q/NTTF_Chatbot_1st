<?php

namespace App\Models\CommonModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CentreCategoryTypes extends Model
{
    use HasFactory,SoftDeletes,LogsActivity;
    public $table = 'centre_category_types';
    protected $fillable = [
        'CategoryCode',
        'uuid',
        'CategoryName',
        'Status',
        'CreatedOn',
        'CreatedBy',
        'ModifiedOn',
        'ModifiedBy',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
       
    }
}
