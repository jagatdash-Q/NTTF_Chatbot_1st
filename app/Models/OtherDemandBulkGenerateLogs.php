<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtherDemandBulkGenerateLogs extends Model
{
    protected $fillable = ['program_id', 'stream_id', 'center_id','academic_year_id','created_by','total_applicable_students','remarks','semester','is_reverted','other_demand_code','demand_reverse_remarks','demand_reverse_date'];
}
