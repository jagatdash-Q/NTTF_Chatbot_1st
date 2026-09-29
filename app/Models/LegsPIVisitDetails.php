<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegsPIVisitDetails extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_id',
        'date',
        'description',
        'planned_action',
        'target_date',
        'actual_date',
        'remarks',
        'legs_employee_id',
        'legs_employee_name',
    ];

    public function entry()
    {
        return $this->belongsTo(LegsPIVisit::class,  'visit_id', 'id',);
    }
}
