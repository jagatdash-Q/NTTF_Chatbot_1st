<?php

namespace App\Models;

use App\Models\CommonModels\FeeQuota;
use App\Models\CommonModels\QuotaSystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class QuotaMaster extends Model
{
    use SoftDeletes;
    protected $table = 'quota_masters';
    protected $fillable = [
        'quota_name',
        'program',
        'reg_quota',
        'center',
        'enrollment_quota',
        'user_rights',
        'is_lateral',
        'is_document_required',
        'document_type',
        'created_by',
        'updated_by',
    ];
    protected $casts = [
        'is_lateral' => 'boolean'
    ];


    public function registrationQuota()
    {
        return $this->belongsTo(QuotaSystem::class, 'reg_quota');
    }

    // Define the enrollmentQuota relationship
    public function enrollmentQuota()
    {
        return $this->belongsTo(FeeQuota::class, 'enrollment_quota');
    }
}
