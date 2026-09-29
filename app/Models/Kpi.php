<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kpi extends Model
{
    protected $fillable = [
        'kra_id_ref',
        'kpi_id',
        'measurement_criteria',
        'target',
        'employee_rating',
        'employee_remark',
        'employee_disagree_note',
        'disagreement_note_c2',
        'ca1_rating',
        'ca1_remark',
        'ca2_rating',
        'ca2_remark',
        'reviewer_rating',
        'reviewer_remark',
        'final_score',
        'old_ca1_rating',
        'old_reviewer_rating',
    ];

    public function kra()
    {
        return $this->belongsTo(Kra::class, 'kra_id_ref');
    }
}
