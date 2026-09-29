<?php

namespace App\Models\CommonModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Centres_Zone_State_CenterCategory extends Model
{
    protected $table = 'centres_zone_state_center_category';

    // Define the fillable fields for mass assignment
    protected $fillable = [
        'Centre_id',
        'Zone_id',
        'Center_category_id',
        'State_id',
    ];

    // Define relationships
    public function centre()
    {
        return $this->belongsTo(Centres::class, 'Centre_id');
    }

    public function zone()
    {
        return $this->belongsTo(Zone::class, 'Zone_id');
    }

    public function centreCategory()
    {
        return $this->belongsTo(CentreCategoryType::class, 'Center_category_id');
    }

    public function state()
    {
        return $this->belongsTo(State::class, 'State_id');
    }
}
