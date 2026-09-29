<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kra extends Model
{
    protected $fillable = [
        'appraisal_id',
        'kra_id',
        'kra_name',
        'weightage',
        'emp_final_score',
        'ca1_final_score',
        'ca2_final_score',
        'final_score',
    ];

    public function appraisal()
    {
        return $this->belongsTo(Appraisal::class);
    }

    public function kpis()
    {
        return $this->hasMany(Kpi::class, 'kra_id_ref');
    }
}
