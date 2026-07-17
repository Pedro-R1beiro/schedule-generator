<?php

namespace App\Services;

use App\Models\PublisherPairPreference;
use App\Models\Publisher;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class PublisherPairPreferenceService
{
    /**
     * Verificar se os publishers são diferentes
     */
    private function validateDifferentPublishers(int $requesterId, int $preferredId): void
    {
        if ($requesterId === $preferredId) {
            throw ValidationException::withMessages([
                'preferred_publisher_id' => 'Não é possível criar preferência para o mesmo publisher.'
            ]);
        }
    }

    /**
     * Verificar se os publishers estão ativos
     */
    private function validateActivePublishers(int $requesterId, int $preferredId): void
    {
        $requester = Publisher::find($requesterId);
        $preferred = Publisher::find($preferredId);

        if (!$requester || !$requester->is_active) {
            throw ValidationException::withMessages([
                'requester_publisher_id' => 'O publisher solicitante está inativo ou não existe.'
            ]);
        }

        if (!$preferred || !$preferred->is_active) {
            throw ValidationException::withMessages([
                'preferred_publisher_id' => 'O publisher preferido está inativo ou não existe.'
            ]);
        }
    }

    /**
     * Verificar se a preferência já existe
     */
    private function validateExistingPreference(int $requesterId, int $preferredId): void
    {
        $exists = $this->hasPreference($requesterId, $preferredId);
        
        if ($exists) {
            throw ValidationException::withMessages([
                'preferred_publisher_id' => 'Esta preferência já existe.'
            ]);
        }
    }

    /**
     * Verificar o modo do publisher (apenas aviso, não bloqueia)
     */
    private function checkPublisherMode(int $publisherId): array
    {
        $publisher = Publisher::find($publisherId);
        if (!$publisher) {
            return ['mode' => null, 'label' => 'Desconhecido'];
        }

        $modes = [
            'ONLY' => 'Somente',
            'PRIORITY' => 'Prioridade'
        ];

        return [
            'mode' => $publisher->pairing_preference_mode,
            'label' => $modes[$publisher->pairing_preference_mode] ?? 'Desconhecido'
        ];
    }

    /**
     * Criar uma nova preferência
     */
    public function create(array $data): PublisherPairPreference
    {
        $requesterId = (int) $data['requester_publisher_id'];
        $preferredId = (int) $data['preferred_publisher_id'];

        // Validações
        $this->validateDifferentPublishers($requesterId, $preferredId);
        $this->validateActivePublishers($requesterId, $preferredId);
        $this->validateExistingPreference($requesterId, $preferredId);

        // Verificar modo do requester (apenas para informação)
        $modeInfo = $this->checkPublisherMode($requesterId);

        $preference = PublisherPairPreference::create($data);

        // Adicionar informação do modo ao objeto retornado
        $preference->mode_info = $modeInfo;

        return $preference;
    }

    /**
     * Deletar uma preferência
     */
    public function delete(int $id): array
    {
        $preference = $this->getById($id);
        
        $data = [
            'id' => $preference->id,
            'requester_publisher_id' => $preference->requester_publisher_id,
            'preferred_publisher_id' => $preference->preferred_publisher_id,
            'requester_name' => $preference->requesterPublisher->name,
            'preferred_name' => $preference->preferredPublisher->name,
            'full_name' => $preference->full_name,
            'requester_mode' => $preference->requesterPublisher->pairing_preference_mode
        ];

        $preference->delete();

        return [
            'message' => 'Preferência deletada com sucesso.',
            'data' => $data
        ];
    }

    /**
     * Buscar uma preferência por ID
     */
    public function getById(int $id): PublisherPairPreference
    {
        try {
            return PublisherPairPreference::with([
                'requesterPublisher',
                'preferredPublisher'
            ])->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Preferência não encontrada.');
        }
    }

    /**
     * Buscar todas as preferências
     */
    public function getAll(): Collection
    {
        return PublisherPairPreference::with([
            'requesterPublisher',
            'preferredPublisher'
        ])->orderBy('id')->get();
    }

    /**
     * Buscar preferências por requester
     */
    public function getByRequester(int $publisherId): Collection
    {
        // Verificar se o publisher existe
        $publisher = Publisher::find($publisherId);
        if (!$publisher) {
            throw new \Exception('Publisher não encontrado.');
        }

        return PublisherPairPreference::with([
            'requesterPublisher',
            'preferredPublisher'
        ])->forRequester($publisherId)->get();
    }

    /**
     * Buscar preferências por preferred
     */
    public function getByPreferred(int $publisherId): Collection
    {
        // Verificar se o publisher existe
        $publisher = Publisher::find($publisherId);
        if (!$publisher) {
            throw new \Exception('Publisher não encontrado.');
        }

        return PublisherPairPreference::with([
            'requesterPublisher',
            'preferredPublisher'
        ])->forPreferred($publisherId)->get();
    }

    /**
     * Buscar preferência por par de publishers
     */
    public function getByPair(int $publisherOneId, int $publisherTwoId): ?PublisherPairPreference
    {
        return PublisherPairPreference::forPair($publisherOneId, $publisherTwoId)->first();
    }

    /**
     * Verificar se existe preferência específica
     */
    public function hasPreference(int $requesterId, int $preferredId): bool
    {
        return PublisherPairPreference::hasPreference($requesterId, $preferredId);
    }

    /**
     * Verificar se existe preferência em qualquer direção
     */
    public function hasAnyPreference(int $publisherId1, int $publisherId2): bool
    {
        return PublisherPairPreference::hasAnyPreference($publisherId1, $publisherId2);
    }

    /**
     * Buscar publishers preferidos por um publisher específico
     */
    public function getPreferredPublishers(int $publisherId): Collection
    {
        // Verificar se o publisher existe
        $publisher = Publisher::find($publisherId);
        if (!$publisher) {
            throw new \Exception('Publisher não encontrado.');
        }

        return Publisher::whereIn('id', function ($query) use ($publisherId) {
            $query->select('preferred_publisher_id')
                  ->from('publisher_pair_preferences')
                  ->where('requester_publisher_id', $publisherId);
        })->get();
    }

    /**
     * Buscar publishers que preferem um publisher específico
     */
    public function getPublishersThatPreferMe(int $publisherId): Collection
    {
        // Verificar se o publisher existe
        $publisher = Publisher::find($publisherId);
        if (!$publisher) {
            throw new \Exception('Publisher não encontrado.');
        }

        return Publisher::whereIn('id', function ($query) use ($publisherId) {
            $query->select('requester_publisher_id')
                  ->from('publisher_pair_preferences')
                  ->where('preferred_publisher_id', $publisherId);
        })->get();
    }

    /**
     * Buscar preferências por modo do requester
     */
    public function getPreferencesByMode(string $mode): Collection
    {
        $validModes = ['ONLY', 'PRIORITY'];
        if (!in_array($mode, $validModes)) {
            throw new \Exception('Modo inválido. Use ONLY ou PRIORITY.');
        }

        return PublisherPairPreference::with([
            'requesterPublisher',
            'preferredPublisher'
        ])->whereHas('requesterPublisher', function ($query) use ($mode) {
            $query->where('pairing_preference_mode', $mode);
        })->get();
    }

    /**
     * Obter resumo das preferências de um publisher
     */
    public function getPreferencesSummary(int $publisherId): array
    {
        // Verificar se o publisher existe
        $publisher = Publisher::find($publisherId);
        if (!$publisher) {
            throw new \Exception('Publisher não encontrado.');
        }

        $preferencesMade = $this->getByRequester($publisherId);
        $preferencesReceived = $this->getByPreferred($publisherId);
        $preferredPublishers = $this->getPreferredPublishers($publisherId);
        $publishersThatPreferMe = $this->getPublishersThatPreferMe($publisherId);

        $modeInfo = $this->checkPublisherMode($publisherId);

        return [
            'publisher' => [
                'id' => $publisher->id,
                'name' => $publisher->name,
                'is_active' => $publisher->is_active,
                'pairing_preference_mode' => $publisher->pairing_preference_mode,
                'mode_label' => $modeInfo['label']
            ],
            'preferences_made' => [
                'count' => $preferencesMade->count(),
                'list' => $preferencesMade->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'preferred_publisher' => $p->preferredPublisher->name,
                        'preferred_mode' => $p->preferredPublisher->pairing_preference_mode
                    ];
                })
            ],
            'preferences_received' => [
                'count' => $preferencesReceived->count(),
                'list' => $preferencesReceived->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'requester_publisher' => $p->requesterPublisher->name,
                        'requester_mode' => $p->requesterPublisher->pairing_preference_mode
                    ];
                })
            ],
            'preferred_publishers' => $preferredPublishers->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'pairing_preference_mode' => $p->pairing_preference_mode
                ];
            })->toArray(),
            'publishers_that_prefer_me' => $publishersThatPreferMe->map(function ($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'pairing_preference_mode' => $p->pairing_preference_mode
                ];
            })->toArray()
        ];
    }

    /**
     * Buscar todas as preferências com detalhes completos para API
     */
    public function getForApi(): Collection
    {
        return $this->getAll()->map(function ($preference) {
            return [
                'id' => $preference->id,
                'requester_publisher' => [
                    'id' => $preference->requesterPublisher->id,
                    'name' => $preference->requesterPublisher->name,
                    'is_active' => $preference->requesterPublisher->is_active,
                    'pairing_preference_mode' => $preference->requesterPublisher->pairing_preference_mode
                ],
                'preferred_publisher' => [
                    'id' => $preference->preferredPublisher->id,
                    'name' => $preference->preferredPublisher->name,
                    'is_active' => $preference->preferredPublisher->is_active,
                    'pairing_preference_mode' => $preference->preferredPublisher->pairing_preference_mode
                ],
                'full_name' => $preference->full_name,
                'full_name_with_mode' => $preference->full_name_with_mode,
                'requester_mode_label' => $preference->getRequesterModeLabel(),
                'created_at' => $preference->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $preference->updated_at?->format('Y-m-d H:i:s')
            ];
        });
    }

    /**
     * Verificar se uma preferência pode ser deletada
     */
    public function canDelete(int $id): array
    {
        $preference = $this->getById($id);

        return [
            'can_delete' => true,
            'id' => $id,
            'full_name' => $preference->full_name,
            'requester_mode' => $preference->requesterPublisher->pairing_preference_mode
        ];
    }

    /**
     * Obter estatísticas de preferências
     */
    public function getStatistics(): array
    {
        $total = PublisherPairPreference::count();
        $onlyMode = Publisher::where('pairing_preference_mode', 'ONLY')->count();
        $priorityMode = Publisher::where('pairing_preference_mode', 'PRIORITY')->count();
        $preferencesOnly = $this->getPreferencesByMode('ONLY')->count();
        $preferencesPriority = $this->getPreferencesByMode('PRIORITY')->count();

        return [
            'total_preferences' => $total,
            'publishers_by_mode' => [
                'ONLY' => $onlyMode,
                'PRIORITY' => $priorityMode
            ],
            'preferences_by_mode' => [
                'ONLY' => $preferencesOnly,
                'PRIORITY' => $preferencesPriority
            ]
        ];
    }
}