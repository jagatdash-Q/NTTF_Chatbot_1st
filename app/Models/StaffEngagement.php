<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffEngagement extends Model
{
    use HasFactory;

    protected $table = 'staff_engagements';

    protected $fillable = [
        'user_id',
        'engagement_type',
        'program_id',
        'stream_id',
        'external_training_id',
        'date',
        'total_hour','general_program','remarks'
    ];

   

}