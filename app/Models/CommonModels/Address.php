<?php

namespace App\Models\CommonModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\CommonModels\Zones;

class Address extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;
    public $table = 'addresses';
    protected $fillable = [
        'user_id',
        'address_line1',
        'address_line2',
        'address_line3',
        'city',
        'state_or_province',
        'district',
        'address_for',
        'postal_code',
        'country',
        'latitude',
        'longitude',
        'address_type',
        'address_status',
        'address_category',
        'is_primary',
        'person_id',
        'created_by',
        'updated_by',
        'other_address',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
    public function zone()
    {
        return $this->belongsTo(Zones::class);
    }
}
