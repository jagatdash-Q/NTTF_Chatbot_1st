<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrincipalReportCover extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'center_id',
        'month_year',
        'title',
        'photo_path',
        'photo_type',
    ];

    protected $appends = [
        'photo_url',
    ];

    public function getPhotoUrlAttribute()
    {
        return $this->photo_path
            ? asset('storage/' . $this->photo_path)
            : null;
    }

    public function user()
    {
        return $this->belongsTo(
            \App\Models\User::class,
            'user_id'
        );
    }

    public function center()
    {
        return $this->belongsTo(
            \App\Models\CommonModels\Centres::class,
            'center_id'
        );
    }
}