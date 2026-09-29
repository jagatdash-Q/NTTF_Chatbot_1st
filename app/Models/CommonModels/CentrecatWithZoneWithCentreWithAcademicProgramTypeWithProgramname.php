<?php



namespace App\Models\CommonModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class CentrecatWithZoneWithCentreWithAcademicProgramTypeWithProgramname extends Model
{
    use HasFactory,  LogsActivity;
    public $table = 'addresses';
    protected $fillable = ['center_program_id','zone_id','centre_id',
    'academic_programme_id','college_academic_program_stream_id'];
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
