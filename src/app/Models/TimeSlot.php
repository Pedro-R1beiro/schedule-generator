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

    // Relacionamento com FixedPair
    public function fixedPairs()
    {
        return $this->hasMany(FixedPair::class);
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

    public function scopeOrdered($query)
    {
        return $query->orderBy('start_time');
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

    // Verifica se o horário é válido
    public function isValid(): bool
    {
        return strtotime($this->start_time) < strtotime($this->end_time);
    }

    // Retorna duração em minutos
    public function getDurationInMinutesAttribute(): int
    {
        $start = \Carbon\Carbon::parse($this->start_time);
        $end = \Carbon\Carbon::parse($this->end_time);
        return $start->diffInMinutes($end);
    }

    // Verifica se o horário tem relacionamentos
    public function hasRelationships(): bool
    {
        return $this->weekdayTimeSlots()->exists() || $this->fixedPairs()->exists();
    }

    // Verifica se pode ser deletado
    public function canBeDeleted(): bool
    {
        return !$this->hasRelationships();
    }

    // Retorna os relacionamentos em formato legível
    public function getRelationshipsSummaryAttribute(): array
    {
        $summary = [];
        
        $weekdayCount = $this->weekdayTimeSlots()->count();
        if ($weekdayCount > 0) {
            $summary['weekday_time_slots'] = $weekdayCount;
        }
        
        $fixedPairCount = $this->fixedPairs()->count();
        if ($fixedPairCount > 0) {
            $summary['fixed_pairs'] = $fixedPairCount;
        }
        
        return $summary;
    }
}