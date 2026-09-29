<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScheduleSemesterTest extends Model
{
    protected $fillable  = ['zone_id','industry_id','batch_id','programe_id','semester_id','date','start_time','end_time','test_name','month_year'];
}
