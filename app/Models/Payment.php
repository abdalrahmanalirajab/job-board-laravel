<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'employer_id',
        'application_id',
        'amount',
        'currency',
        'provider',
        'stripe_payment_intent_id',
        'stripe_client_secret',
        'stripe_session_id',
        'stripe_event_id',
        'metadata',
        'status',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'paid_at'  => 'datetime',
            'amount'   => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function employer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_id');
    }

    public function application(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Application::class);
    }
}
