<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoreDemandMigrationAdditionalInfo extends Model
{
    protected $fillable = [
        'import_id',
        'enrollment_center',
        'center',
        'planvouchernumber',
        'studentcode'
    ];
}
