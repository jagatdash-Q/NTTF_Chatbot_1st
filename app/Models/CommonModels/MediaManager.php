<?php

namespace App\Models\CommonModels;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Support\Facades\Storage;

class MediaManager extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;
    protected $fillable = [
        'user_id',
        'user_type',
        'document_type',
        'file_name',
        'file_path',
        'file_size',
        'file_format',
        'upload_date',
        'created_by',
        'updated_by',
        'modified_by',
        'description',
        'is_active',
        'document_file_type',
    ];
    public $table = 'media_managers';

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults();
    }
     // Method to store file and save metadata
     public static function storeFile($file, $additionalData = [])
     {
         $filePath = $file->store('media'); // Store file in "media" folder
         $fileData = [
             'file_name' => $file->getClientOriginalName(),
             'file_path' => $filePath,
             'file_size' => $file->getSize(),
             'file_format' => $file->getClientOriginalExtension(),
             'upload_date' => now(),
         ];
         
         return self::create(array_merge($fileData, $additionalData));
     }
}
