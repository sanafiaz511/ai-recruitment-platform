<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobListing extends Model
{
     protected $fillable = [
        'company_id',
        'title',
        'slug',
        'description',
        'requirements',
        'location',
        'type',
        'experience_level',
        'salary_min',
        'salary_max',
        'is_active'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function applications()
    {
        return $this->hasMany(Application::class);
    }
}
