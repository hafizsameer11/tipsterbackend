<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tip extends Model
{
    use HasFactory;
    protected $fillable = [
        'user_id',
        'betting_company_id',
        'codes',
        'ods',
        'status',
        'result',
        'match_date',
        'betting_category'
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bettingCompany()
    {
        return $this->belongsTo(BettingCompany::class);
    }
}
