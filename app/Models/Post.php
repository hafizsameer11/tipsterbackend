<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;
    protected $fillable = [
        'title',
        'content',
        'has_image',
        'user_id',
        'image_1',
        'image_2',
        'image_3',
        'image_4',
        'status'
    ];
}
