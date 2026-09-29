<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\Uid\UuidV7;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    protected $fillable = [
        'uuid',
        'notification_triggered_by',
        'triggered_person_name',
        'notification_for',
        'notification_for_name',
        'role_name',
        'entity_type',
        'title',
        'description',
        'created_by',
        'updated_by',
        'status' ,  
    ];

    /**
     *  Setup model event hooks
     */
    public static function boot()
    {
        parent::boot();
        self::creating(function ($model) {
            $model->uuid = new UuidV7();
        });
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}