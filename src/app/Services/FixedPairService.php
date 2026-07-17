<?php

namespace App\Services;

use App\Models\FixedPair;
use App\Models\Publisher;
use App\Models\WeekdayTimeSlot;
use App\Models\PublisherPairRestriction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class FixedPairService
{
    /**
     * Normalizar os IDs dos publishers (menor sempre em publisher_one)
     */
    private function normalizePublisherIds(array $data): array
    {
        if (isset($data['publisher_one_id']) && isset($data['publisher_two_id'])) {
            $p1 = (int) $data['publisher_one_id'];
            $p2 = (int) $data['publisher_two_id'];
            
            if ($p1 > $p2) {
                return [
                    'publisher_one_id' => $p2,
                    'publisher_two_id' => $p1
                ];
            }
        }
        return $data;
    }

    /**
     * Verificar se os publishers são diferentes
     */
    private function validateDifferentPublishers(int $publisherOneId, int $publisherTwoId): void
    {
        if ($publisherOneId === $publisherTwoId) {
            throw ValidationException::withMessages([
                'publisher_two_id' => 'Os publishers devem ser diferentes.'
            ]);
        }
    }

    /**
     * Verificar se os publishers estão ativos
     */
    private function validateActivePublishers(int $publisherOneId, int $publisherTwoId): void
    {
        $publisherOne = Publisher::find($publisherOneId);
        $publisherTwo = Publisher::find($publisherTwoId);

        if (!$publisherOne || !$publisherOne->is_active) {
            throw ValidationException::withMessages([
                'publisher_one_id' => 'O primeiro publisher está inativo ou não existe.'
            ]);
        }

        if (!$publisherTwo || !$publisherTwo->is_active) {
            throw ValidationException::withMessages([
                'publisher_two_id' => 'O segundo publisher está inativo ou não existe.'
            ]);
        }
    }

    /**
     * Verificar se o weekday_time_slot existe e é válido
     */
    private function validateWeekdayTimeSlot(int $weekdayTimeSlotId): void
    {
        $weekdayTimeSlot = WeekdayTimeSlot::with(['timeSlot'])->find($weekdayTimeSlotId);
        
        if (!$weekdayTimeSlot) {
            throw ValidationException::withMessages([
                'weekday_time_slot_id' => 'O dia/horário selecionado não existe.'
            ]);
        }

        if (!$weekdayTimeSlot->timeSlot->is_active) {
            throw ValidationException::withMessages([
                'weekday_time_slot_id' => 'O horário deste dia/horário está inativo.'
            ]);
        }
    }

    /**
     * Verificar se já existe um par fixo para o mesmo dia/horário
     */
    private function validateExistingPair(int $weekdayTimeSlotId, int $publisherOneId, int $publisherTwoId): void
    {
        $exists = $this->checkExistingPair($weekdayTimeSlotId, $publisherOneId, $publisherTwoId);
        
        if ($exists) {
            throw ValidationException::withMessages([
                'weekday_time_slot_id' => 'Já existe um par fixo para este dia/horário com estes publishers.'
            ]);
        }
    }

    /**
     * Verificar se existe restrição entre os publishers
     */
    private function validateRestrictions(int $publisherOneId, int $publisherTwoId): void
    {
        $hasRestriction = $this->checkRestrictions($publisherOneId, $publisherTwoId);
        
        if ($hasRestriction) {
            throw ValidationException::withMessages([
                'publisher_two_id' => 'Existe uma restrição entre estes publishers.'
            ]);
        }
    }

    /**
     * Criar um novo par fixo
     */
    public function create(array $data): FixedPair
    {
        // Normalizar IDs (publisher_one sempre o menor)
        $data = $this->normalizePublisherIds($data);
        
        $publisherOneId = (int) $data['publisher_one_id'];
        $publisherTwoId = (int) $data['publisher_two_id'];
        $weekdayTimeSlotId = (int) $data['weekday_time_slot_id'];

        // Validações
        $this->validateDifferentPublishers($publisherOneId, $publisherTwoId);
        $this->validateActivePublishers($publisherOneId, $publisherTwoId);
        $this->validateWeekdayTimeSlot($weekdayTimeSlotId);
        $this->validateExistingPair($weekdayTimeSlotId, $publisherOneId, $publisherTwoId);
        $this->validateRestrictions($publisherOneId, $publisherTwoId);

        return FixedPair::create($data);
    }

    /**
     * Atualizar um par fixo
     */
    public function update(int $id, array $data): FixedPair
    {
        $fixedPair = $this->getById($id);

        // Não permitir alterar weekday_time_slot_id
        if (isset($data['weekday_time_slot_id'])) {
            throw ValidationException::withMessages([
                'weekday_time_slot_id' => 'Não é permitido alterar o dia/horário de um par fixo.'
            ]);
        }

        // Normalizar IDs se ambos foram fornecidos
        if (isset($data['publisher_one_id']) && isset($data['publisher_two_id'])) {
            $data = $this->normalizePublisherIds($data);
            $publisherOneId = (int) $data['publisher_one_id'];
            $publisherTwoId = (int) $data['publisher_two_id'];

            // Validações apenas se os publishers forem alterados
            if ($publisherOneId !== $fixedPair->publisher_one_id || 
                $publisherTwoId !== $fixedPair->publisher_two_id) {
                
                $this->validateDifferentPublishers($publisherOneId, $publisherTwoId);
                $this->validateActivePublishers($publisherOneId, $publisherTwoId);
                
                // Verificar se já existe par com os novos publishers no mesmo dia/horário
                $exists = $this->checkExistingPair(
                    $fixedPair->weekday_time_slot_id,
                    $publisherOneId,
                    $publisherTwoId
                );
                
                if ($exists) {
                    throw ValidationException::withMessages([
                        'publisher_two_id' => 'Já existe um par fixo para este dia/horário com estes publishers.'
                    ]);
                }
                
                $this->validateRestrictions($publisherOneId, $publisherTwoId);
            }
        } elseif (isset($data['publisher_one_id']) || isset($data['publisher_two_id'])) {
            throw ValidationException::withMessages([
                'publisher_one_id' => 'Para alterar os publishers, ambos devem ser fornecidos.'
            ]);
        }

        $fixedPair->update($data);
        return $fixedPair->fresh();
    }

    /**
     * Deletar um par fixo (permanentemente)
     */
    public function delete(int $id): array
    {
        $fixedPair = $this->getById($id);
        
        $data = [
            'id' => $fixedPair->id,
            'publisher_one_id' => $fixedPair->publisher_one_id,
            'publisher_two_id' => $fixedPair->publisher_two_id,
            'publisher_one_name' => $fixedPair->publisherOne->name,
            'publisher_two_name' => $fixedPair->publisherTwo->name,
            'weekday_time_slot_id' => $fixedPair->weekday_time_slot_id,
            'full_name' => $fixedPair->full_name
        ];

        $fixedPair->delete();

        return [
            'message' => 'Par fixo deletado com sucesso.',
            'data' => $data
        ];
    }

    /**
     * Buscar um par fixo por ID
     */
    public function getById(int $id): FixedPair
    {
        try {
            return FixedPair::with([
                'publisherOne',
                'publisherTwo',
                'weekdayTimeSlot.weekday',
                'weekdayTimeSlot.timeSlot'
            ])->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Par fixo não encontrado.');
        }
    }

    /**
     * Buscar todos os pares fixos
     */
    public function getAll(): Collection
    {
        return FixedPair::with([
            'publisherOne',
            'publisherTwo',
            'weekdayTimeSlot.weekday',
            'weekdayTimeSlot.timeSlot'
        ])->ordered()->get();
    }

    /**
     * Buscar pares fixos por weekday_time_slot
     */
    public function getByWeekdayTimeSlot(int $weekdayTimeSlotId): Collection
    {
        // Verificar se o weekday_time_slot existe
        $weekdayTimeSlot = WeekdayTimeSlot::find($weekdayTimeSlotId);
        if (!$weekdayTimeSlot) {
            throw new \Exception('Dia/horário não encontrado.');
        }

        return FixedPair::with([
            'publisherOne',
            'publisherTwo'
        ])->forWeekdayTimeSlot($weekdayTimeSlotId)->get();
    }

    /**
     * Buscar pares fixos por publisher
     */
    public function getByPublisher(int $publisherId): Collection
    {
        // Verificar se o publisher existe
        $publisher = Publisher::find($publisherId);
        if (!$publisher) {
            throw new \Exception('Publisher não encontrado.');
        }

        return FixedPair::with([
            'publisherOne',
            'publisherTwo',
            'weekdayTimeSlot.weekday',
            'weekdayTimeSlot.timeSlot'
        ])->forPublisher($publisherId)->ordered()->get();
    }

    /**
     * Buscar pares fixos por ambos os publishers
     */
    public function getByPublishers(int $publisherOneId, int $publisherTwoId): Collection
    {
        // Normalizar IDs
        if ($publisherOneId > $publisherTwoId) {
            $temp = $publisherOneId;
            $publisherOneId = $publisherTwoId;
            $publisherTwoId = $temp;
        }

        return FixedPair::with([
            'publisherOne',
            'publisherTwo',
            'weekdayTimeSlot.weekday',
            'weekdayTimeSlot.timeSlot'
        ])->where('publisher_one_id', $publisherOneId)
          ->where('publisher_two_id', $publisherTwoId)
          ->ordered()
          ->get();
    }

    /**
     * Verificar se existe restrição entre dois publishers
     */
    public function checkRestrictions(int $publisherOneId, int $publisherTwoId): bool
    {
        return PublisherPairRestriction::hasAnyRestriction($publisherOneId, $publisherTwoId);
    }

    /**
     * Verificar se já existe um par fixo
     */
    public function checkExistingPair(int $weekdayTimeSlotId, int $publisherOneId, int $publisherTwoId): bool
    {
        // Normalizar IDs
        if ($publisherOneId > $publisherTwoId) {
            $temp = $publisherOneId;
            $publisherOneId = $publisherTwoId;
            $publisherTwoId = $temp;
        }

        return FixedPair::where('weekday_time_slot_id', $weekdayTimeSlotId)
                        ->where('publisher_one_id', $publisherOneId)
                        ->where('publisher_two_id', $publisherTwoId)
                        ->exists();
    }

    /**
     * Buscar publishers disponíveis para um slot (que não têm restrições)
     */
    public function getAvailablePublishersForSlot(int $weekdayTimeSlotId): Collection
    {
        // Verificar se o weekday_time_slot existe
        $weekdayTimeSlot = WeekdayTimeSlot::with(['timeSlot'])->find($weekdayTimeSlotId);
        if (!$weekdayTimeSlot) {
            throw new \Exception('Dia/horário não encontrado.');
        }

        // Buscar publishers que já estão em pares neste slot
        $existingPublisherIds = FixedPair::where('weekday_time_slot_id', $weekdayTimeSlotId)
                                         ->get()
                                         ->flatMap(function ($pair) {
                                             return [$pair->publisher_one_id, $pair->publisher_two_id];
                                         })
                                         ->unique()
                                         ->toArray();

        // Buscar publishers ativos que não estão no slot
        $availablePublishers = Publisher::where('is_active', true)
                                        ->whereNotIn('id', $existingPublisherIds)
                                        ->orderBy('name')
                                        ->get();

        return $availablePublishers;
    }

    /**
     * Buscar pares fixos com detalhes completos para API
     */
    public function getForApi(): Collection
    {
        return $this->getAll()->map(function ($pair) {
            return [
                'id' => $pair->id,
                'publisher_one' => [
                    'id' => $pair->publisherOne->id,
                    'name' => $pair->publisherOne->name,
                    'is_active' => $pair->publisherOne->is_active,
                    'is_pioneer' => $pair->publisherOne->is_pioneer
                ],
                'publisher_two' => [
                    'id' => $pair->publisherTwo->id,
                    'name' => $pair->publisherTwo->name,
                    'is_active' => $pair->publisherTwo->is_active,
                    'is_pioneer' => $pair->publisherTwo->is_pioneer
                ],
                'weekday_time_slot_id' => $pair->weekday_time_slot_id,
                'weekday' => [
                    'id' => $pair->weekdayTimeSlot->weekday->id ?? null,
                    'name' => $pair->weekdayTimeSlot->weekday->name ?? null,
                    'display_order' => $pair->weekdayTimeSlot->weekday->display_order ?? null
                ],
                'time_slot' => [
                    'id' => $pair->weekdayTimeSlot->timeSlot->id ?? null,
                    'name' => $pair->weekdayTimeSlot->timeSlot->name ?? null,
                    'start_time' => $pair->weekdayTimeSlot->timeSlot->start_time ?? null,
                    'end_time' => $pair->weekdayTimeSlot->timeSlot->end_time ?? null
                ],
                'has_restrictions' => $pair->hasRestrictions(),
                'full_name' => $pair->full_name,
                'created_at' => $pair->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $pair->updated_at?->format('Y-m-d H:i:s')
            ];
        });
    }
}