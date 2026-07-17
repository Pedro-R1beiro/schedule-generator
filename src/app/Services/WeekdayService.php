<?php

namespace App\Services;

use App\Models\Weekday;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class WeekdayService
{
    /**
     * Obter todos os dias da semana ordenados
     */
    public function getAll(): Collection
    {
        return Weekday::ordered()->get();
    }

    /**
     * Obter todos os dias da semana com seus relacionamentos
     */
    public function getAllWithRelationships(): Collection
    {
        return Weekday::ordered()
                      ->with(['weekdayTimeSlots', 'weekdayTimeSlots.timeSlot'])
                      ->get();
    }

    /**
     * Buscar um dia por ID
     */
    public function getById(int $id): Weekday
    {
        try {
            return Weekday::with(['weekdayTimeSlots', 'weekdayTimeSlots.timeSlot'])
                          ->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Dia da semana não encontrado.');
        }
    }

    /**
     * Buscar um dia por nome
     */
    public function getByName(string $name): ?Weekday
    {
        return Weekday::where('name', $name)->first();
    }

    /**
     * Buscar um dia por nome (case insensitive)
     */
    public function getByNameInsensitive(string $name): ?Weekday
    {
        return Weekday::whereRaw('LOWER(name) = ?', [strtolower($name)])->first();
    }

    /**
     * Obter apenas dias ativos (que têm horários ativos)
     */
    public function getActive(): Collection
    {
        return Weekday::ordered()
                      ->whereHas('weekdayTimeSlots', function ($query) {
                          $query->whereHas('timeSlot', function ($q) {
                              $q->where('is_active', true);
                          });
                      })
                      ->get();
    }

    /**
     * Obter dias com contagem de relacionamentos
     */
    public function getWithCounts(): Collection
    {
        return Weekday::ordered()
                      ->withCount(['weekdayTimeSlots'])
                      ->get();
    }

    /**
     * Obter dias com detalhes completos para API
     */
    public function getForApi(): Collection
    {
        return Weekday::ordered()
                      ->with(['weekdayTimeSlots' => function ($query) {
                          $query->with(['timeSlot' => function ($q) {
                              $q->where('is_active', true);
                          }]);
                      }])
                      ->get()
                      ->map(function ($weekday) {
                          return [
                              'id' => $weekday->id,
                              'name' => $weekday->name,
                              'display_order' => $weekday->display_order,
                              'name_pt' => $weekday->getNameInPortuguese(),
                              'is_active' => $weekday->hasActiveTimeSlots(),
                              'time_slots' => $weekday->getActiveTimeSlots(),
                              'weekday_time_slots_count' => $weekday->weekdayTimeSlots->count(),
                              'created_at' => $weekday->created_at?->format('Y-m-d H:i:s'),
                              'updated_at' => $weekday->updated_at?->format('Y-m-d H:i:s')
                          ];
                      });
    }

    /**
     * Verificar se um dia existe
     */
    public function exists(int $id): bool
    {
        return Weekday::where('id', $id)->exists();
    }

    /**
     * Verificar se um dia está em uso
     */
    public function isUsed(int $id): bool
    {
        $weekday = $this->getById($id);
        return $weekday->isUsed();
    }

    /**
     * Obter o próximo dia da semana
     */
    public function getNextWeekday(int $id): ?Weekday
    {
        $current = $this->getById($id);
        return Weekday::where('display_order', '>', $current->display_order)
                      ->orderBy('display_order')
                      ->first();
    }

    /**
     * Obter o dia anterior da semana
     */
    public function getPreviousWeekday(int $id): ?Weekday
    {
        $current = $this->getById($id);
        return Weekday::where('display_order', '<', $current->display_order)
                      ->orderBy('display_order', 'desc')
                      ->first();
    }

    /**
     * Obter dias com seus horários formatados
     */
    public function getFormattedWeekdays(): array
    {
        $weekdays = $this->getAll();
        $result = [];

        foreach ($weekdays as $weekday) {
            $timeSlots = $weekday->timeSlots()
                                 ->where('is_active', true)
                                 ->orderBy('start_time')
                                 ->get();

            $result[] = [
                'id' => $weekday->id,
                'name' => $weekday->name,
                'name_pt' => $weekday->getNameInPortuguese(),
                'display_order' => $weekday->display_order,
                'time_slots' => $timeSlots->map(function ($slot) {
                    return [
                        'id' => $slot->id,
                        'name' => $slot->name,
                        'start_time' => $slot->start_time,
                        'end_time' => $slot->end_time
                    ];
                }),
                'total_time_slots' => $timeSlots->count()
            ];
        }

        return $result;
    }
}