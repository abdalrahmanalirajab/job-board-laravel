<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobTechnology extends Model
{
    protected $fillable = ['job_listing_id', 'name'];

    public function jobListing(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(JobListing::class);
    }
}
