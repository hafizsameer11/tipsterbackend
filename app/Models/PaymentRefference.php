<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentRefference extends Model
{
    use HasFactory;
    protected $fillable = [
        'reference',
        'email',
        'status',
        'amount',
    ];
}
