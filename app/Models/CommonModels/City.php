<?php

namespace App\Models\CommonModels;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class City extends Model
{
    public $fillable = [
        'state_id',
        'name',
        'status'
    ];
    public function getState(): HasOne
    {
        return $this->hasOne(States::class, 'id', 'state_id')->with('getCountry');
    }
}
