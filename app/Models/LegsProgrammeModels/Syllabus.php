<?php


namespace App\Models\LegsProgrammeModels;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Syllabus extends Model
{
    use HasFactory, LogsActivity;
    protected $fillable = [
        'uuid',
        'semester_id',
        'subject_id',
        'industry_partner',
        'syllabus_code',
        'syllabus_data',
        'programe_id',
        'topic_group',
        'topic_name',
        'topic_code',
        'start_date',
        'end_date',
        'no_of_class',
        'description', 'created_by',
        'updated_by','group_hour','topic_hour'
    ];
    public $table = 'syllabus';
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
}
