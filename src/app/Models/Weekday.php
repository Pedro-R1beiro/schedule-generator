<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Weekday extends Model
{
    use HasFactory;

    protected $table = 'weekdays';
    
    protected $fillable = [
        'name',
        'display_order'
    ];

    protected $casts = [
        'display_order' => 'integer'
    ];

    // Relacionamento com WeekdayTimeSlot
    public function weekdayTimeSlots()
    {
        return $this->hasMany(WeekdayTimeSlot::class);
    }

    // Relacionamento com TimeSlot através de weekday_time_slots
    public function timeSlots()
    {
        return $this->belongsToMany(TimeSlot::class, 'weekday_time_slots')
                    ->withTimestamps();
    }

    // Scope para ordenar por display_order
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }
}
