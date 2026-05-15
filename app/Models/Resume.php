<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Resume extends Model
{
    protected $fillable = [
        'user_id',
        'file_path',
        'original_name',
        'parsed_data',
        'skills',
        'experience_years',
        'strengths',
        'weaknesses',
        'summary',
        'score',
        'raw_ai_response'
    ];

    protected $casts = [
        'parsed_data' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
