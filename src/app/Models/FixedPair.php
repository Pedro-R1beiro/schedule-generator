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

    // Verifica se um publisher está no par
    public function hasPublisher(int $publisherId): bool
    {
        return $this->publisher_one_id === $publisherId || 
               $this->publisher_two_id === $publisherId;
    }

    // Retorna o outro publisher do par
    public function getOtherPublisher(int $publisherId): ?Publisher
    {
        if ($this->publisher_one_id === $publisherId) {
            return $this->publisherTwo;
        }
        if ($this->publisher_two_id === $publisherId) {
            return $this->publisherOne;
        }
        return null;
    }

    // Verifica se há restrição entre os publishers
    public function hasRestrictions(): bool
    {
        return PublisherPairRestriction::hasAnyRestriction(
            $this->publisher_one_id,
            $this->publisher_two_id
        );
    }

    // Scope para filtrar por publisher
    public function scopeForPublisher($query, int $publisherId)
    {
        return $query->where('publisher_one_id', $publisherId)
                     ->orWhere('publisher_two_id', $publisherId);
    }

    // Scope para filtrar por weekday_time_slot
    public function scopeForWeekdayTimeSlot($query, int $weekdayTimeSlotId)
    {
        return $query->where('weekday_time_slot_id', $weekdayTimeSlotId);
    }

    // Scope para ordenar
    public function scopeOrdered($query)
    {
        return $query->join('weekday_time_slots', 'fixed_pairs.weekday_time_slot_id', '=', 'weekday_time_slots.id')
                     ->join('weekdays', 'weekday_time_slots.weekday_id', '=', 'weekdays.id')
                     ->join('time_slots', 'weekday_time_slots.time_slot_id', '=', 'time_slots.id')
                     ->orderBy('weekdays.display_order')
                     ->orderBy('time_slots.start_time')
                     ->select('fixed_pairs.*');
    }

    // Retorna nome completo para exibição
    public function getFullNameAttribute(): string
    {
        return $this->publisherOne->name . ' x ' . $this->publisherTwo->name;
    }
}