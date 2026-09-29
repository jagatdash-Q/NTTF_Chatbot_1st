<?php

namespace App\Models\CommonModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CenterPrograme extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['centertype_id','programe_name','programe_code','status','company_branch_name']; 
    protected $table = 'center_programes';
}
