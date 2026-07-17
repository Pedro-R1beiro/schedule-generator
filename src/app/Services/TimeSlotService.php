<?php

namespace App\Services;

use App\Models\TimeSlot;
use App\Models\WeekdayTimeSlot;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TimeSlotService
{
    /**
     * Gerar o nome do horário baseado no start_time e end_time
     */
    public function generateName(string $startTime, string $endTime): string
    {
        $start = date('H:i', strtotime($startTime));
        $end = date('H:i', strtotime($endTime));
        return $start . ' - ' . $end;
    }

    /**
     * Verificar se há sobreposição de horários
     */
    public function hasOverlap(string $startTime, string $endTime, ?int $excludeId = null): bool
    {
        $query = TimeSlot::where(function ($query) use ($startTime, $endTime) {
            // Verifica se o novo horário sobrepõe algum existente
            $query->where(function ($q) use ($startTime, $endTime) {
                // Novo horário começa dentro de um horário existente
                $q->where('start_time', '<', $endTime)
                  ->where('end_time', '>', $startTime);
            });
        });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Verificar se há conflito com horário específico
     */
    public function hasConflictWithTimeSlot(
        string $startTime, 
        string $endTime, 
        int $timeSlotId
    ): bool {
        $timeSlot = TimeSlot::find($timeSlotId);
        if (!$timeSlot) {
            return false;
        }

        return $this->hasOverlap($startTime, $endTime, $timeSlotId);
    }

    /**
     * Criar um novo horário
     */
    public function create(array $data): TimeSlot
    {
        // Gerar nome automaticamente
        $data['name'] = $this->generateName($data['start_time'], $data['end_time']);
        
        // Verificar sobreposição
        if ($this->hasOverlap($data['start_time'], $data['end_time'])) {
            throw ValidationException::withMessages([
                'start_time' => 'Este horário sobrepõe um horário existente.',
                'end_time' => 'Este horário sobrepõe um horário existente.'
            ]);
        }

        // Validar se start_time é menor que end_time
        if (strtotime($data['start_time']) >= strtotime($data['end_time'])) {
            throw ValidationException::withMessages([
                'end_time' => 'O horário final deve ser maior que o horário inicial.'
            ]);
        }

        return TimeSlot::create($data);
    }

    /**
     * Atualizar um horário
     */
    public function update(int $id, array $data): TimeSlot
    {
        $timeSlot = TimeSlot::findOrFail($id);

        // Gerar nome automaticamente se start_time ou end_time foram alterados
        if (isset($data['start_time']) || isset($data['end_time'])) {
            $startTime = $data['start_time'] ?? $timeSlot->start_time;
            $endTime = $data['end_time'] ?? $timeSlot->end_time;
            $data['name'] = $this->generateName($startTime, $endTime);
            
            // Verificar sobreposição (excluindo o próprio registro)
            if ($this->hasOverlap($startTime, $endTime, $id)) {
                throw ValidationException::withMessages([
                    'start_time' => 'Este horário sobrepõe um horário existente.',
                    'end_time' => 'Este horário sobrepõe um horário existente.'
                ]);
            }

            // Validar se start_time é menor que end_time
            if (strtotime($startTime) >= strtotime($endTime)) {
                throw ValidationException::withMessages([
                    'end_time' => 'O horário final deve ser maior que o horário inicial.'
                ]);
            }
        }

        $timeSlot->update($data);
        return $timeSlot->fresh();
    }

    /**
     * Deletar um horário
     * Se tiver filhos (weekday_time_slots), apenas desativa
     * Se não tiver filhos, deleta permanentemente
     */
    public function delete(int $id): array
    {
        $timeSlot = TimeSlot::findOrFail($id);
        
        // Verificar se tem filhos (weekday_time_slots)
        $hasChildren = $timeSlot->weekdayTimeSlots()->exists();
        
        $action = '';
        $message = '';

        if ($hasChildren) {
            // Tem filhos: apenas desativa
            $timeSlot->update(['is_active' => false]);
            $action = 'desativado';
            $message = 'Horário desativado pois possui relacionamentos ativos.';
        } else {
            // Não tem filhos: deleta permanentemente
            $timeSlot->delete();
            $action = 'deletado';
            $message = 'Horário deletado permanentemente.';
        }

        return [
            'action' => $action,
            'message' => $message,
            'id' => $id
        ];
    }

    /**
     * Deletar permanentemente um horário (forçar delete)
     */
    public function forceDelete(int $id): array
    {
        $timeSlot = TimeSlot::findOrFail($id);
        
        // Deletar todos os relacionamentos primeiro
        $timeSlot->weekdayTimeSlots()->delete();
        
        // Deletar o horário
        $timeSlot->delete();

        return [
            'action' => 'deletado_permanentemente',
            'message' => 'Horário e todos os seus relacionamentos foram deletados permanentemente.',
            'id' => $id
        ];
    }

    /**
     * Verificar se um horário pode ser deletado
     */
    public function canDelete(int $id): bool
    {
        $timeSlot = TimeSlot::findOrFail($id);
        return !$timeSlot->weekdayTimeSlots()->exists();
    }

    /**
     * Obter todos os horários ativos
     */
    public function getActiveTimeSlots(): Collection
    {
        return TimeSlot::where('is_active', true)->orderBy('start_time')->get();
    }

    /**
     * Obter todos os horários (incluindo inativos)
     */
    public function getAllTimeSlots(): Collection
    {
        return TimeSlot::orderBy('start_time')->get();
    }

    /**
     * Obter horário por ID
     */
    public function getTimeSlotById(int $id): TimeSlot
    {
        return TimeSlot::findOrFail($id);
    }

    /**
     * Verificar se horário está sendo usado
     */
    public function isUsed(int $id): bool
    {
        $timeSlot = TimeSlot::findOrFail($id);
        return $timeSlot->weekdayTimeSlots()->exists();
    }

    /**
     * Ativar um horário desativado
     */
    public function activate(int $id): TimeSlot
    {
        $timeSlot = TimeSlot::findOrFail($id);
        
        // Verificar se o horário está desativado
        if ($timeSlot->is_active) {
            throw ValidationException::withMessages([
                'id' => 'Este horário já está ativo.'
            ]);
        }

        // Verificar se há sobreposição com horários ativos
        if ($this->hasOverlap($timeSlot->start_time, $timeSlot->end_time, $id)) {
            throw ValidationException::withMessages([
                'start_time' => 'Não é possível ativar pois há conflito com outro horário ativo.'
            ]);
        }

        $timeSlot->update(['is_active' => true]);
        return $timeSlot->fresh();
    }

    /**
     * Desativar um horário
     */
    public function deactivate(int $id): TimeSlot
    {
        $timeSlot = TimeSlot::findOrFail($id);
        
        if (!$timeSlot->is_active) {
            throw ValidationException::withMessages([
                'id' => 'Este horário já está desativado.'
            ]);
        }

        $timeSlot->update(['is_active' => false]);
        return $timeSlot->fresh();
    }
}