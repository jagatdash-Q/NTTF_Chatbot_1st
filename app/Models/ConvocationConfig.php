<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\CommonModels\Centres;

class ConvocationConfig extends Model
{
    protected $fillable = [
        'convocation_name',
        'convocation_year',
        'convocation_date',
        'venue_center_id',
        'venue_name',
        'notes',
        'attachment_path',
         'guest_attachment'
    ];

    public function center()
    {
        return $this->belongsTo(Centres::class, 'venue_center_id');
    }
}
