<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    protected $fillable = [
        'job_listing_id',
        'candidate_id',
        'resume_path',
        'contact_email',
        'contact_phone',
        'status',
        'applied_at',
    ];

    protected $casts = [
        'status' => 'string',
        'applied_at' => 'datetime',
    ];

    public function jobListing()
    {
        return $this->belongsTo(JobListing::class);
    }

    public function candidate()
    {
        return $this->belongsTo(User::class, 'candidate_id');
    }
}