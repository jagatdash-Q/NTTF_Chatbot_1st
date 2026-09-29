<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LegsExamCellMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'uuid',
        'name',
        'email',
        'password',
        'status'
    ];

    /**
     * Get the user that owns the exam cell member.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}