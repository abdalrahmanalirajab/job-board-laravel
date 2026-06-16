<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Employer extends Model
{
    protected $fillable = ['user_id', 'company_name', 'logo', 'website', 'description'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
