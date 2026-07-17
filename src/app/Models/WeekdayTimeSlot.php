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

    // Relacionamento com FixedPair
    public function fixedPairs()
    {
        return $this->hasMany(FixedPair::class);
    }

    // Verifica se tem fixed_pairs
    public function hasFixedPairs(): bool
    {
        return $this->fixedPairs()->exists();
    }

    // Retorna contagem de fixed_pairs
    public function getFixedPairsCount(): int
    {
        return $this->fixedPairs()->count();
    }

    // Scope para filtrar por weekday
    public function scopeForWeekday($query, int $weekdayId)
    {
        return $query->where('weekday_id', $weekdayId);
    }

    // Scope para filtrar por time_slot
    public function scopeForTimeSlot($query, int $timeSlotId)
    {
        return $query->where('time_slot_id', $timeSlotId);
    }

    // Scope para ordenar por weekday e time_slot
    public function scopeOrdered($query)
    {
        return $query->join('weekdays', 'weekday_time_slots.weekday_id', '=', 'weekdays.id')
                     ->join('time_slots', 'weekday_time_slots.time_slot_id', '=', 'time_slots.id')
                     ->orderBy('weekdays.display_order')
                     ->orderBy('time_slots.start_time')
                     ->select('weekday_time_slots.*');
    }

    // Retorna nome completo para exibição
    public function getFullNameAttribute(): string
    {
        return $this->weekday->name . ' - ' . $this->timeSlot->name;
    }

    // Retorna nome em português
    public function getFullNamePtAttribute(): string
    {
        return $this->weekday->getNameInPortuguese() . ' - ' . $this->timeSlot->name;
    }
}