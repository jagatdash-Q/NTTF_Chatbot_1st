<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgramBranch extends Model
{
    protected $fillable = [
     
        'program_id',
        'program_type',
        'stream_id',
        'stream_name',
        'highest_qualification_id', 'qualification_name',       
        'branch_id',
        'branch_name',
        'created_by',
        'updated_by'
    ];
}