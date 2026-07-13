<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_listing_id',
        'candidate_user_id',
        'employer_user_id',
        'last_message_at',
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(JobListing::class, 'job_listing_id');
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'candidate_user_id');
    }

    public function employer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_user_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function otherParticipant(int $userId): ?User
    {
        if ($this->candidate_user_id === $userId) {
            return $this->employer;
        }
        if ($this->employer_user_id === $userId) {
            return $this->candidate;
        }
        return null;
    }

    public function unreadCount(int $userId): int
    {
        return $this->messages()
            ->where('sender_user_id', '!=', $userId)
            ->whereNull('read_at')
            ->count();
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('candidate_user_id', $userId)
            ->orWhere('employer_user_id', $userId);
    }
}
