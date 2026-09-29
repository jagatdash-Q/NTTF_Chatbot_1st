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

class Inductiontimetable extends Model
{
    use HasFactory, Notifiable, LogsActivity;
    public $table = 'inductiontimetables';
    protected $fillable = [
        'student_batch_id',
        'induction_day',
        'day_number',
        'morning_activities',
        'knowledge_theory',
        'break',
        'skill_practical',
        'outcome',
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
