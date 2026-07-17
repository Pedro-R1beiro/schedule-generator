<?php

namespace App\Services;

use App\Models\WeekdayTimeSlot;
use App\Models\Weekday;
use App\Models\TimeSlot;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class WeekdayTimeSlotService
{
    /**
     * Criar um novo relacionamento dia-horário
     */
    public function create(array $data): WeekdayTimeSlot
    {
        // Verificar se o weekday existe
        $weekday = Weekday::find($data['weekday_id']);
        if (!$weekday) {
            throw ValidationException::withMessages([
                'weekday_id' => 'O dia da semana informado não existe.'
            ]);
        }

        // Verificar se o time_slot existe
        $timeSlot = TimeSlot::find($data['time_slot_id']);
        if (!$timeSlot) {
            throw ValidationException::withMessages([
                'time_slot_id' => 'O horário informado não existe.'
            ]);
        }

        // Verificar se o time_slot está ativo
        if (!$timeSlot->is_active) {
            throw ValidationException::withMessages([
                'time_slot_id' => 'O horário informado está inativo.'
            ]);
        }

        // Verificar se o par já existe
        $exists = WeekdayTimeSlot::where('weekday_id', $data['weekday_id'])
                                 ->where('time_slot_id', $data['time_slot_id'])
                                 ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'time_slot_id' => 'Este horário já está associado a este dia da semana.'
            ]);
        }

        return WeekdayTimeSlot::create($data);
    }

    /**
     * Deletar um relacionamento dia-horário
     */
    public function delete(int $id): array
    {
        $weekdayTimeSlot = $this->getById($id);

        // Verificar se existem fixed_pairs relacionados
        $hasFixedPairs = $weekdayTimeSlot->hasFixedPairs();
        $fixedPairsCount = $weekdayTimeSlot->getFixedPairsCount();

        if ($hasFixedPairs) {
            throw ValidationException::withMessages([
                'id' => "Não é possível deletar este relacionamento pois existem {$fixedPairsCount} pares fixos associados."
            ]);
        }

        // Salvar dados para retorno
        $data = [
            'id' => $weekdayTimeSlot->id,
            'weekday_id' => $weekdayTimeSlot->weekday_id,
            'time_slot_id' => $weekdayTimeSlot->time_slot_id,
            'weekday_name' => $weekdayTimeSlot->weekday->name,
            'time_slot_name' => $weekdayTimeSlot->timeSlot->name,
        ];

        // Deletar
        $weekdayTimeSlot->delete();

        return [
            'message' => 'Relacionamento dia-horário deletado com sucesso.',
            'data' => $data
        ];
    }

    /**
     * Buscar um relacionamento por ID
     */
    public function getById(int $id): WeekdayTimeSlot
    {
        try {
            return WeekdayTimeSlot::with(['weekday', 'timeSlot', 'fixedPairs'])
                                  ->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Relacionamento dia-horário não encontrado.');
        }
    }

    /**
     * Buscar todos os relacionamentos
     */
    public function getAll(): Collection
    {
        return WeekdayTimeSlot::with(['weekday', 'timeSlot'])
                              ->ordered()
                              ->get();
    }

    /**
     * Buscar relacionamentos por weekday
     */
    public function getByWeekday(int $weekdayId): Collection
    {
        // Verificar se o weekday existe
        $weekday = Weekday::find($weekdayId);
        if (!$weekday) {
            throw new \Exception('Dia da semana não encontrado.');
        }

        return WeekdayTimeSlot::with(['timeSlot'])
                              ->forWeekday($weekdayId)
                              ->whereHas('timeSlot', function ($query) {
                                  $query->where('is_active', true);
                              })
                              ->orderBy('time_slots.start_time')
                              ->get();
    }

    /**
     * Buscar relacionamentos por time_slot
     */
    public function getByTimeSlot(int $timeSlotId): Collection
    {
        // Verificar se o time_slot existe
        $timeSlot = TimeSlot::find($timeSlotId);
        if (!$timeSlot) {
            throw new \Exception('Horário não encontrado.');
        }

        return WeekdayTimeSlot::with(['weekday'])
                              ->forTimeSlot($timeSlotId)
                              ->orderBy('weekdays.display_order')
                              ->get();
    }

    /**
     * Buscar relacionamento por weekday e time_slot
     */
    public function getByWeekdayAndTimeSlot(int $weekdayId, int $timeSlotId): ?WeekdayTimeSlot
    {
        return WeekdayTimeSlot::where('weekday_id', $weekdayId)
                              ->where('time_slot_id', $timeSlotId)
                              ->first();
    }

    /**
     * Buscar horários disponíveis para um weekday
     * (horários que ainda não estão associados ao dia)
     */
    public function getAvailableTimeSlots(int $weekdayId): Collection
    {
        // Verificar se o weekday existe
        $weekday = Weekday::find($weekdayId);
        if (!$weekday) {
            throw new \Exception('Dia da semana não encontrado.');
        }

        // Buscar IDs dos time_slots já associados
        $associatedIds = WeekdayTimeSlot::where('weekday_id', $weekdayId)
                                        ->pluck('time_slot_id')
                                        ->toArray();

        // Buscar time_slots ativos que não estão associados
        return TimeSlot::where('is_active', true)
                       ->whereNotIn('id', $associatedIds)
                       ->orderBy('start_time')
                       ->get();
    }

    /**
     * Verificar se um relacionamento tem fixed_pairs
     */
    public function hasFixedPairs(int $id): bool
    {
        $weekdayTimeSlot = $this->getById($id);
        return $weekdayTimeSlot->hasFixedPairs();
    }

    /**
     * Buscar relacionamentos com dados completos para API
     */
    public function getForApi(): Collection
    {
        return WeekdayTimeSlot::with(['weekday', 'timeSlot'])
                              ->ordered()
                              ->get()
                              ->map(function ($item) {
                                  return [
                                      'id' => $item->id,
                                      'weekday' => [
                                          'id' => $item->weekday->id,
                                          'name' => $item->weekday->name,
                                          'name_pt' => $item->weekday->getNameInPortuguese(),
                                          'display_order' => $item->weekday->display_order
                                      ],
                                      'time_slot' => [
                                          'id' => $item->timeSlot->id,
                                          'name' => $item->timeSlot->name,
                                          'start_time' => $item->timeSlot->start_time,
                                          'end_time' => $item->timeSlot->end_time,
                                          'is_active' => $item->timeSlot->is_active
                                      ],
                                      'full_name' => $item->full_name,
                                      'full_name_pt' => $item->full_name_pt,
                                      'fixed_pairs_count' => $item->getFixedPairsCount(),
                                      'has_fixed_pairs' => $item->hasFixedPairs(),
                                      'created_at' => $item->created_at?->format('Y-m-d H:i:s'),
                                      'updated_at' => $item->updated_at?->format('Y-m-d H:i:s')
                                  ];
                              });
    }

    /**
     * Verificar se um relacionamento existe
     */
    public function exists(int $id): bool
    {
        return WeekdayTimeSlot::where('id', $id)->exists();
    }

    /**
     * Verificar se um par existe
     */
    public function pairExists(int $weekdayId, int $timeSlotId): bool
    {
        return WeekdayTimeSlot::where('weekday_id', $weekdayId)
                              ->where('time_slot_id', $timeSlotId)
                              ->exists();
    }

    /**
     * Buscar relacionamentos com fixed_pairs
     */
    public function getWithFixedPairs(): Collection
    {
        return WeekdayTimeSlot::with(['weekday', 'timeSlot', 'fixedPairs'])
                              ->whereHas('fixedPairs')
                              ->ordered()
                              ->get();
    }

    /**
     * Buscar relacionamentos sem fixed_pairs
     */
    public function getWithoutFixedPairs(): Collection
    {
        return WeekdayTimeSlot::with(['weekday', 'timeSlot'])
                              ->whereDoesntHave('fixedPairs')
                              ->ordered()
                              ->get();
    }
}