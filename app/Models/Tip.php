<?php

namespace App\Models;

use Carbon\Carbon;
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
    protected $appends = ['match_date'];

    // Accessor to format match_date like created_at (ISO 8601 format)
    public function getMatchDateAttribute($value)
    {
        return $value ? Carbon::createFromFormat('d-m-Y', $value)->toISOString() : null;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bettingCompany()
    {
        return $this->belongsTo(BettingCompany::class);
    }
}
