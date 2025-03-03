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
        'images',
        'status',
        'share_count',
        'view_count',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
    public function likes()
    {
        return $this->belongsToMany(User::class, 'likes');
    }

    public function shares()
    {
        return $this->hasMany(PostShare::class);
    }
}
