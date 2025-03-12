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
        'betting_category',
        'rejection_reason'
    ];
    protected $appends = ['formatted_match_date']; // Ensures it's included in API responses

    // Convert match_date (dd-mm-yyyy) to ISO 8601 format (same as created_at)
    public function getFormattedMatchDateAttribute()
    {
        if (!isset($this->attributes['match_date']) || empty($this->attributes['match_date'])) {
            return null; // Return null if match_date is not set
        }

        try {
            return Carbon::createFromFormat('d-m-Y', $this->attributes['match_date'])->toISOString();
        } catch (\Exception $e) {
            return null; // Return null if conversion fails
        }
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
