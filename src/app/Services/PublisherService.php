<?php

namespace App\Services;

use App\Models\Publisher;
use App\Models\FixedPair;
use App\Models\PublisherPairRestriction;
use App\Models\PublisherPairPreference;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class PublisherService
{
    /**
     * Validar se o nome é único
     */
    private function validateUniqueName(string $name, ?int $excludeId = null): void
    {
        $query = Publisher::where('name', $name);
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        if ($query->exists()) {
            throw ValidationException::withMessages([
                'name' => 'Já existe um publisher com este nome.'
            ]);
        }
    }

    /**
     * Validar o start_day
     */
    private function validateStartDay(int $startDay): void
    {
        if ($startDay < 1 || $startDay > 31) {
            throw ValidationException::withMessages([
                'start_day' => 'O dia de início deve estar entre 1 e 31.'
            ]);
        }
    }

    /**
     * Validar o gênero
     */
    private function validateGender(string $gender): void
    {
        if (!in_array($gender, ['M', 'F'])) {
            throw ValidationException::withMessages([
                'gender' => 'O gênero deve ser M ou F.'
            ]);
        }
    }

    /**
     * Validar o modo de preferência
     */
    private function validatePreferenceMode(string $mode): void
    {
        if (!in_array($mode, ['ONLY', 'PRIORITY'])) {
            throw ValidationException::withMessages([
                'pairing_preference_mode' => 'O modo de preferência deve ser ONLY ou PRIORITY.'
            ]);
        }
    }

    /**
     * Criar um novo publisher
     */
    public function create(array $data): Publisher
    {
        // Validações
        $this->validateUniqueName($data['name']);
        $this->validateStartDay((int) $data['start_day']);
        $this->validateGender($data['gender']);
        $this->validatePreferenceMode($data['pairing_preference_mode']);

        // Garantir valores padrão
        $data['is_active'] = $data['is_active'] ?? true;
        $data['is_manual'] = $data['is_manual'] ?? false;
        $data['is_pioneer'] = $data['is_pioneer'] ?? false;
        $data['monthly_limit'] = $data['monthly_limit'] ?? 4;
        $data['weekly_limit'] = $data['weekly_limit'] ?? 2;

        return Publisher::create($data);
    }

    /**
     * Atualizar um publisher
     */
    public function update(int $id, array $data): Publisher
    {
        $publisher = $this->getById($id);

        // Validar nome se foi alterado
        if (isset($data['name']) && $data['name'] !== $publisher->name) {
            $this->validateUniqueName($data['name'], $id);
        }

        // Validar start_day se foi alterado
        if (isset($data['start_day'])) {
            $this->validateStartDay((int) $data['start_day']);
        }

        // Validar gênero se foi alterado
        if (isset($data['gender'])) {
            $this->validateGender($data['gender']);
        }

        // Validar modo de preferência se foi alterado
        if (isset($data['pairing_preference_mode'])) {
            $this->validatePreferenceMode($data['pairing_preference_mode']);
        }

        $publisher->update($data);
        return $publisher->fresh();
    }

    /**
     * Desativar um publisher (soft delete)
     */
    public function delete(int $id): array
    {
        $publisher = $this->getById($id);

        // Verificar se já está inativo
        if (!$publisher->is_active) {
            throw ValidationException::withMessages([
                'id' => 'Este publisher já está inativo.'
            ]);
        }

        // Verificar se pode ser desativado
        $canDeactivateInfo = $publisher->canBeDeactivated();

        if (!$canDeactivateInfo['can_deactivate']) {
            $warnings = [];
            if ($canDeactivateInfo['has_fixed_pairs']) {
                $warnings[] = "possui {$canDeactivateInfo['fixed_pairs_count']} par(es) fixo(s)";
            }
            if ($canDeactivateInfo['has_restrictions']) {
                $warnings[] = "possui {$canDeactivateInfo['restrictions_count']} restrição(ões)";
            }
            if ($canDeactivateInfo['has_preferences']) {
                $warnings[] = "possui {$canDeactivateInfo['preferences_count']} preferência(s)";
            }

            throw ValidationException::withMessages([
                'id' => 'Não é possível desativar este publisher pois ' . implode(' e ', $warnings) . '.'
            ]);
        }

        // Desativar
        $publisher->update(['is_active' => false]);

        return [
            'message' => 'Publisher desativado com sucesso.',
            'data' => [
                'id' => $publisher->id,
                'name' => $publisher->name,
                'was_active' => true,
                'is_active' => false
            ]
        ];
    }

    /**
     * Ativar um publisher
     */
    public function activate(int $id): Publisher
    {
        $publisher = $this->getById($id);

        if ($publisher->is_active) {
            throw ValidationException::withMessages([
                'id' => 'Este publisher já está ativo.'
            ]);
        }

        $publisher->update(['is_active' => true]);
        return $publisher->fresh();
    }

    /**
     * Buscar um publisher por ID
     */
    public function getById(int $id): Publisher
    {
        try {
            return Publisher::with([
                'fixedPairsAsOne',
                'fixedPairsAsTwo',
                'requestedRestrictions',
                'restrictedByOthers',
                'requestedPreferences',
                'preferredByOthers'
            ])->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Publisher não encontrado.');
        }
    }

    /**
     * Buscar todos os publishers
     */
    public function getAll(): Collection
    {
        return Publisher::ordered()->get();
    }

    /**
     * Buscar publishers ativos
     */
    public function getActive(): Collection
    {
        return Publisher::active()->ordered()->get();
    }

    /**
     * Buscar publishers por gênero
     */
    public function getByGender(string $gender): Collection
    {
        $this->validateGender($gender);
        return Publisher::byGender($gender)->ordered()->get();
    }

    /**
     * Buscar pioneiros
     */
    public function getPioneers(): Collection
    {
        return Publisher::pioneers()->ordered()->get();
    }

    /**
     * Buscar publishers por modo de preferência
     */
    public function getByPreferenceMode(string $mode): Collection
    {
        $this->validatePreferenceMode($mode);
        
        if ($mode === 'ONLY') {
            return Publisher::preferenceModeOnly()->ordered()->get();
        }
        return Publisher::preferenceModePriority()->ordered()->get();
    }

    /**
     * Obter restrições de um publisher
     */
    public function getWithRestrictions(int $id): array
    {
        $publisher = $this->getById($id);

        return [
            'publisher' => [
                'id' => $publisher->id,
                'name' => $publisher->name
            ],
            'restrictions_made' => $publisher->requestedRestrictions->map(function ($r) {
                return [
                    'id' => $r->id,
                    'restricted_publisher' => $r->restrictedPublisher->name,
                    'created_at' => $r->created_at
                ];
            }),
            'restrictions_received' => $publisher->restrictedByOthers->map(function ($r) {
                return [
                    'id' => $r->id,
                    'requester_publisher' => $r->requesterPublisher->name,
                    'created_at' => $r->created_at
                ];
            }),
            'total_restrictions_made' => $publisher->requestedRestrictions->count(),
            'total_restrictions_received' => $publisher->restrictedByOthers->count()
        ];
    }

    /**
     * Obter preferências de um publisher
     */
    public function getWithPreferences(int $id): array
    {
        $publisher = $this->getById($id);

        return [
            'publisher' => [
                'id' => $publisher->id,
                'name' => $publisher->name,
                'pairing_preference_mode' => $publisher->pairing_preference_mode
            ],
            'preferences_made' => $publisher->requestedPreferences->map(function ($p) {
                return [
                    'id' => $p->id,
                    'preferred_publisher' => $p->preferredPublisher->name,
                    'created_at' => $p->created_at
                ];
            }),
            'preferences_received' => $publisher->preferredByOthers->map(function ($p) {
                return [
                    'id' => $p->id,
                    'requester_publisher' => $p->requesterPublisher->name,
                    'created_at' => $p->created_at
                ];
            }),
            'total_preferences_made' => $publisher->requestedPreferences->count(),
            'total_preferences_received' => $publisher->preferredByOthers->count()
        ];
    }

    /**
     * Obter fixed pairs de um publisher
     */
    public function getWithFixedPairs(int $id): array
    {
        $publisher = $this->getById($id);

        $fixedPairs = FixedPair::where('publisher_one_id', $id)
                               ->orWhere('publisher_two_id', $id)
                               ->with(['publisherOne', 'publisherTwo', 'weekdayTimeSlot.weekday', 'weekdayTimeSlot.timeSlot'])
                               ->get();

        return [
            'publisher' => [
                'id' => $publisher->id,
                'name' => $publisher->name
            ],
            'fixed_pairs' => $fixedPairs->map(function ($pair) use ($id) {
                $otherPublisher = $pair->publisher_one_id === $id 
                    ? $pair->publisherTwo 
                    : $pair->publisherOne;
                
                return [
                    'id' => $pair->id,
                    'with_publisher' => [
                        'id' => $otherPublisher->id,
                        'name' => $otherPublisher->name
                    ],
                    'weekday' => $pair->weekdayTimeSlot->weekday->name ?? null,
                    'time_slot' => $pair->weekdayTimeSlot->timeSlot->name ?? null,
                    'created_at' => $pair->created_at
                ];
            }),
            'total_fixed_pairs' => $fixedPairs->count()
        ];
    }

    /**
     * Obter resumo completo de um publisher
     */
    public function getPublisherSummary(int $id): array
    {
        $publisher = $this->getById($id);
        $canDeactivate = $publisher->canBeDeactivated();

        return [
            'publisher' => [
                'id' => $publisher->id,
                'name' => $publisher->name,
                'phone' => $publisher->phone,
                'is_active' => $publisher->is_active,
                'is_active_label' => $publisher->getActiveLabel(),
                'is_manual' => $publisher->is_manual,
                'is_pioneer' => $publisher->is_pioneer,
                'gender' => $publisher->gender,
                'gender_label' => $publisher->getGenderLabel(),
                'start_day' => $publisher->start_day,
                'monthly_limit' => $publisher->monthly_limit,
                'weekly_limit' => $publisher->weekly_limit,
                'pairing_preference_mode' => $publisher->pairing_preference_mode,
                'pairing_preference_mode_label' => $publisher->getPreferenceModeLabel(),
                'created_at' => $publisher->created_at,
                'updated_at' => $publisher->updated_at
            ],
            'relationships' => $publisher->getRelationshipsCount(),
            'can_be_deactivated' => $canDeactivate['can_deactivate'],
            'deactivation_warnings' => [
                'has_fixed_pairs' => $canDeactivate['has_fixed_pairs'],
                'has_restrictions' => $canDeactivate['has_restrictions'],
                'has_preferences' => $canDeactivate['has_preferences']
            ]
        ];
    }

    /**
     * Verificar disponibilidade de um publisher
     */
    public function checkAvailability(int $id): bool
    {
        $publisher = $this->getById($id);
        
        // Verificar se está ativo
        if (!$publisher->is_active) {
            return false;
        }

        // Verificar se tem restrições ativas
        $hasRestrictions = $publisher->requestedRestrictions()->exists() || 
                          $publisher->restrictedByOthers()->exists();
        
        if ($hasRestrictions) {
            return false;
        }

        return true;
    }

    /**
     * Buscar publishers disponíveis para escalar
     */
    public function getAvailablePublishers(): Collection
    {
        return Publisher::active()
                        ->whereDoesntHave('requestedRestrictions')
                        ->whereDoesntHave('restrictedByOthers')
                        ->ordered()
                        ->get();
    }

    /**
     * Buscar publishers com estatísticas
     */
    public function getStatistics(): array
    {
        $total = Publisher::count();
        $active = Publisher::active()->count();
        $inactive = Publisher::inactive()->count();
        $pioneers = Publisher::pioneers()->count();
        $onlyMode = Publisher::preferenceModeOnly()->count();
        $priorityMode = Publisher::preferenceModePriority()->count();
        $male = Publisher::byGender('M')->count();
        $female = Publisher::byGender('F')->count();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'pioneers' => $pioneers,
            'by_gender' => [
                'male' => $male,
                'female' => $female
            ],
            'by_preference_mode' => [
                'ONLY' => $onlyMode,
                'PRIORITY' => $priorityMode
            ]
        ];
    }
}