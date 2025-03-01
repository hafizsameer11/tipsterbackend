<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResetPassword extends Model
{
    use HasFactory;
    protected $fillable = [
        'email',
        'otp',
        'user_id',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
