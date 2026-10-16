<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeviceAuthorization extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'last_accessed_at' => 'datetime',
        'printer_width' => 'integer',
    ];
}
