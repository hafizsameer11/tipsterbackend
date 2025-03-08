<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RankingPayment extends Model
{
    use HasFactory;
    protected $fillable = ['amount', 'user_id', 'status', 'rank'];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
