<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobListing extends Model
{
    protected $fillable = [
        'employer_id',
        'category_id',
        'title',
        'description',
        'responsibilities',
        'skills_required',
        'salary_min',
        'salary_max',
        'location',
        'work_type',
        'experience_level',
        'status',
        'deadline',
        'logo',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'date',
            'salary_min' => 'integer',
            'salary_max' => 'integer',
        ];
    }

    // Relationships
    public function employer(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'employer_id');
    }

    public function category(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function technologies(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(JobTechnology::class);
    }

    public function applications(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Application::class);
    }

    public function comments(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Comment::class);
    }

    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByWorkType($query, $workType)
    {
        return $query->where('work_type', $workType);
    }

    public function scopeByLocation($query, $location)
    {
        return $query->where('location', $location);
    }

    public function scopeByExperience($query, $level)
    {
        return $query->where('experience_level', $level);
    }

    public function scopeBySalary($query, $min = null, $max = null)
    {
        if ($min !== null) {
            $query->where('salary_min', '>=', $min);
        }
        if ($max !== null) {
            $query->where('salary_max', '<=', $max);
        }
        return $query;
    }

    public function scopeSearch($query, $keyword)
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('title', 'like', "%{$keyword}%")
              ->orWhere('description', 'like', "%{$keyword}%");
        });
    }
}
