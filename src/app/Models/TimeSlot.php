<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TimeSlot extends Model
{
    use HasFactory;

    protected $table = 'time_slots';
    
    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'is_active'
    ];

    protected $casts = [
        'start_time' => 'datetime:H:i:s',
        'end_time' => 'datetime:H:i:s',
        'is_active' => 'boolean'
    ];

    // Relacionamento com WeekdayTimeSlot
    public function weekdayTimeSlots()
    {
        return $this->hasMany(WeekdayTimeSlot::class);
    }

    // Relacionamento com Weekday através de weekday_time_slots
    public function weekdays()
    {
        return $this->belongsToMany(Weekday::class, 'weekday_time_slots')
                    ->withTimestamps();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    // Mutators para garantir formato
    public function setStartTimeAttribute($value)
    {
        $this->attributes['start_time'] = date('H:i:s', strtotime($value));
    }

    public function setEndTimeAttribute($value)
    {
        $this->attributes['end_time'] = date('H:i:s', strtotime($value));
    }
}
