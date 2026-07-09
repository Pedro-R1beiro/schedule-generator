<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Publishers extends Model
{
    use HasFactory;

    protected $table = 'publishers';
    
    protected $fillable = [
        'name',
        'phone',
        'is_active',
        'is_manual',
        'monthly_limit',
        'weekly_limit',
        'is_pioneer',
        'gender',
        'start_day'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_manual' => 'boolean',
        'is_pioneer' => 'boolean',
        'monthly_limit' => 'integer',
        'weekly_limit' => 'integer',
        'start_day' => 'integer'
    ];
}
