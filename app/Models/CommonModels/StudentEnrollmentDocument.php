<?php

namespace App\Models\CommonModels;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class StudentEnrollmentDocument extends Model
{
    use HasFactory, Notifiable, LogsActivity, SoftDeletes;
    protected $fillable = [
        'student_enrollment_id',
        'student_lead_id',
        'media_manager_id',
        'document_type',
        'created_by',
        'deleted_by',
        'verify_status', 'verify_by','seen_status', 'seen_by'
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
    public function getDocumentFile(): HasOne
    {
        return $this->hasOne(MediaManager::class, 'id', 'media_manager_id');
    }
    public function getCreatedBy(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }
}
