<?php

namespace App\Models\LegsProgrammeModels;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Ropeintimetable extends Model
{
    use HasFactory, Notifiable, LogsActivity;
    public $table = 'ropeintimetables';
    protected $fillable = [
        'student_batch_id',
        'rope_in_day',
        'day_number',
        'eight_to_nine',
        'nine_to_ten',
        'ten_to_ten_fifteen',
        'ten_fifteen_to_one',
        'one_to_one_thirty',
        'one_thirty_to_three',
        'three_fifteen_to_three_thirty',
        'three_fifteen_to_five',
        'created_by',
        'updated_by',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
    // public function company()
    // {
    //     return $this->belongsTo(Company::class);
    // }

    // public function program()
    // {
    //     return $this->belongsTo(Program::class);
    // }
    
}
