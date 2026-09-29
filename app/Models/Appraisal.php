<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appraisal extends Model
{
    protected $fillable = [
        'id',
        'reference',
        'employee_name',
        'employee_id',
        'year',
        'quarter',
        'state',
        'approved_by_ca1',
        'approved_by_ca2',
        'final_score_float',
        'employee_remark',
        'manager_remark',
        'ca2_remark',
        'reviewer_remark',
        'employee_disagree_note',
        'disagreement_note_c2',
        'ca1_score_float',
        'ca2_score_float',
        'approved_by_reviewer',
        'old_ca1_remark',
        'old_reviewer_remark',
    ];

    public function kras()
    {
        return $this->hasMany(Kra::class);
    }
}
