<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FixedPair extends Model
{
    use HasFactory;

    protected $table = 'fixed_pairs';
    
    protected $fillable = [
        'publisher_one_id',
        'publisher_two_id',
        'weekday_time_slot_id'
    ];

    protected $casts = [
        'publisher_one_id' => 'integer',
        'publisher_two_id' => 'integer',
        'weekday_time_slot_id' => 'integer'
    ];

    // Relacionamento com o primeiro publisher
    public function publisherOne()
    {
        return $this->belongsTo(Publisher::class, 'publisher_one_id');
    }

    // Relacionamento com o segundo publisher
    public function publisherTwo()
    {
        return $this->belongsTo(Publisher::class, 'publisher_two_id');
    }

    // Relacionamento com weekday_time_slot
    public function weekdayTimeSlot()
    {
        return $this->belongsTo(WeekdayTimeSlot::class);
    }

    // Acesso ao weekday através do relacionamento
    public function getWeekdayAttribute()
    {
        return $this->weekdayTimeSlot->weekday ?? null;
    }

    // Acesso ao time_slot através do relacionamento
    public function getTimeSlotAttribute()
    {
        return $this->weekdayTimeSlot->timeSlot ?? null;
    }

    // Scope para filtrar por publisher
    public function scopeForPublisher($query, $publisherId)
    {
        return $query->where('publisher_one_id', $publisherId)
                     ->orWhere('publisher_two_id', $publisherId);
    }

    // Scope para filtrar por weekday_time_slot
    public function scopeForWeekdayTimeSlot($query, $weekdayTimeSlotId)
    {
        return $query->where('weekday_time_slot_id', $weekdayTimeSlotId);
    }

    // Verifica se um publisher está no par
    public function hasPublisher($publisherId)
    {
        return $this->publisher_one_id === $publisherId || 
               $this->publisher_two_id === $publisherId;
    }

    // Retorna o outro publisher do par
    public function getOtherPublisher($publisherId)
    {
        if ($this->publisher_one_id === $publisherId) {
            return $this->publisherTwo;
        }
        if ($this->publisher_two_id === $publisherId) {
            return $this->publisherOne;
        }
        return null;
    }
}
