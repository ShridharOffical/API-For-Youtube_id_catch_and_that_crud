<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class YoutubeVideo extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        'video_id',
        'thumbnail',
        'is_active',
        // Add any other fields that can be mass-assigned
    ];

}
