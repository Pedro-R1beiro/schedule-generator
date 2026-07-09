<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WeekdayTimeSlot extends Model
{
    use HasFactory;

    protected $table = 'weekday_time_slots';
    
    protected $fillable = [
        'weekday_id',
        'time_slot_id'
    ];

    protected $casts = [
        'weekday_id' => 'integer',
        'time_slot_id' => 'integer'
    ];

    // Relacionamento com Weekday
    public function weekday()
    {
        return $this->belongsTo(Weekday::class);
    }

    // Relacionamento com TimeSlot
    public function timeSlot()
    {
        return $this->belongsTo(TimeSlot::class);
    }
}
