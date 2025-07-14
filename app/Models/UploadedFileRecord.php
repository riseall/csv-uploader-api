<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UploadedFileRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'data_type',
        'original_file_name',
        'mime_type',
        'file_path',
        'upload_date',
    ];

    protected $casts = [
        'upload_date' => 'date',
    ];

    protected $table = 'uploaded_file_records';
}
