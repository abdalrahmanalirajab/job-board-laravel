<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Candidate extends Model
{
    protected $fillable = ['user_id', 'resume_path', 'linkedin_url', 'bio', 'phone', 'skills'];

    // handles json and store it as array in db
    protected $casts = [
        'skills' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
