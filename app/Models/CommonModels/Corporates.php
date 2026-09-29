<?php

namespace App\Models\CommonModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use App\Models\CommonModels\Address;
use App\Models\CommonModels\ContactDetails;
use App\Models\CommonModels\MediaManager;

class Corporates extends Model
{
    use HasFactory,LogsActivity;
   

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
       
    }
   
    protected $table = 'corporates';

   
    protected $fillable = [
        'corporate_code',
        'uuid',
        'corporate_name',
        'corporate_short_name',
        'corporate_address_id',
        'contact_details_id', 
        'corporate_email',
        'corporate_contact_no',
        'media_manager_id',
        'corporate_logo_id',
        'corporate_status',
        'created_by',
        'modified_on',
        'modified_by'
    ];
    public function address()
    {
        return $this->belongsTo(Address::class, 'corporate_address_id');
    }

    public function contact()
    {
        return $this->belongsTo(ContactDetails::class, 'contact_details_id');
    }
  
    public function mediamanager()
    {
        return $this->belongsTo(MediaManager::class, 'media_manager_id');
    }
}
