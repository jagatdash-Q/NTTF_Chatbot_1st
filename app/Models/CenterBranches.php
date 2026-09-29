<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\CommonModels\Address;
use App\Models\CommonModels\Centres;

class CenterBranches extends Model
{
    use HasFactory;

    protected $table = 'center_branches';

    protected $fillable = [
        'centre_id',
        'branch_name',
        'address_id',
        'branch_code',
        'branch_status',
        'branch_head_name',
        'branch_head_contact',
        'branch_head_email',
    ];

    public function centre()
    {
        return $this->belongsTo(Centres::class, 'centre_id');
    }

    public function address()
    {
        return $this->belongsTo(Address::class, 'address_id');
    }
}
