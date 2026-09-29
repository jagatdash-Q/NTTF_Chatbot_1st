<?php

namespace App\Models;

use App\Models\CommonModels\Centres;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class CoreStudentRejoinLogs extends Model
{
    protected $fillable = [
        'student_lead_id',
        'new_academic_year',
        'old_academic_year',
        'new_academic_month',
        'old_academic_month',
        'old_center',
        'new_center',
        'old_semestar',
        'new_semestar',
        'change_date',
        'remark',
        'created_by',
    ];

    public function getOldCenterDetails(): HasOne
    {
        return $this->hasOne(Centres::class, 'id', 'old_center');
    }

    public function getNewCenterDetails(): HasOne
    {
        return $this->hasOne(Centres::class, 'id', 'new_center');
    }
}
