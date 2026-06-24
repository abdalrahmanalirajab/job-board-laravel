<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'employer_id',
        'application_id',
        'amount',
        'provider',
        'status',
        'paid_at',
    ];
}
