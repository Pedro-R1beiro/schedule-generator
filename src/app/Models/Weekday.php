<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

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

    // Relacionamento com FixedPair através de weekday_time_slots
    public function fixedPairs()
    {
        return $this->hasManyThrough(
            FixedPair::class,
            WeekdayTimeSlot::class,
            'weekday_id',
            'weekday_time_slot_id'
        );
    }

    // Scopes
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order');
    }

    public function scopeActive($query)
    {
        return $query->whereHas('weekdayTimeSlots', function ($q) {
            $q->whereHas('timeSlot', function ($sq) {
                $sq->where('is_active', true);
            });
        });
    }

    // Verifica se o dia tem horários ativos
    public function hasActiveTimeSlots(): bool
    {
        return $this->weekdayTimeSlots()
                    ->whereHas('timeSlot', function ($q) {
                        $q->where('is_active', true);
                    })
                    ->exists();
    }

    // Retorna os horários ativos do dia
    public function getActiveTimeSlots(): Collection
    {
        return $this->timeSlots()
                    ->where('is_active', true)
                    ->orderBy('start_time')
                    ->get();
    }

    // Retorna contagem de relacionamentos
    public function getRelationshipsCountAttribute(): array
    {
        return [
            'weekday_time_slots' => $this->weekdayTimeSlots()->count(),
            'active_time_slots' => $this->weekdayTimeSlots()
                                        ->whereHas('timeSlot', function ($q) {
                                            $q->where('is_active', true);
                                        })
                                        ->count(),
            'fixed_pairs' => $this->fixedPairs()->count()
        ];
    }

    // Retorna se o dia está em uso
    public function isUsed(): bool
    {
        return $this->weekdayTimeSlots()->exists();
    }

    // Método para obter nome em português
    public function getNameInPortuguese(): string
    {
        $names = [
            'Monday' => 'Segunda-feira',
            'Tuesday' => 'Terça-feira',
            'Wednesday' => 'Quarta-feira',
            'Thursday' => 'Quinta-feira',
            'Friday' => 'Sexta-feira',
            'Saturday' => 'Sábado',
            'Sunday' => 'Domingo'
        ];

        return $names[$this->name] ?? $this->name;
    }
}