<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CoreAccountsDataMigrationRejectedRows extends Model
{
    protected $fillable = [
        'import_id',
        'remarks',
        'row_type',
    ];
}
