<?php

namespace App\Services;

use App\Models\PublisherPairRestriction;
use App\Models\Publisher;
use App\Models\FixedPair;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class PublisherPairRestrictionService
{
    /**
     * Verificar se os publishers são diferentes
     */
    private function validateDifferentPublishers(int $requesterId, int $restrictedId): void
    {
        if ($requesterId === $restrictedId) {
            throw ValidationException::withMessages([
                'restricted_publisher_id' => 'Não é possível criar restrição contra o mesmo publisher.'
            ]);
        }
    }

    /**
     * Verificar se os publishers estão ativos
     */
    private function validateActivePublishers(int $requesterId, int $restrictedId): void
    {
        $requester = Publisher::find($requesterId);
        $restricted = Publisher::find($restrictedId);

        if (!$requester || !$requester->is_active) {
            throw ValidationException::withMessages([
                'requester_publisher_id' => 'O publisher solicitante está inativo ou não existe.'
            ]);
        }

        if (!$restricted || !$restricted->is_active) {
            throw ValidationException::withMessages([
                'restricted_publisher_id' => 'O publisher restrito está inativo ou não existe.'
            ]);
        }
    }

    /**
     * Verificar se a restrição já existe
     */
    private function validateExistingRestriction(int $requesterId, int $restrictedId): void
    {
        $exists = $this->hasRestriction($requesterId, $restrictedId);
        
        if ($exists) {
            throw ValidationException::withMessages([
                'restricted_publisher_id' => 'Esta restrição já existe.'
            ]);
        }
    }

    /**
     * Criar uma nova restrição
     */
    public function create(array $data): PublisherPairRestriction
    {
        $requesterId = (int) $data['requester_publisher_id'];
        $restrictedId = (int) $data['restricted_publisher_id'];

        // Validações
        $this->validateDifferentPublishers($requesterId, $restrictedId);
        $this->validateActivePublishers($requesterId, $restrictedId);
        $this->validateExistingRestriction($requesterId, $restrictedId);

        return PublisherPairRestriction::create($data);
    }

    /**
     * Deletar uma restrição
     */
    public function delete(int $id): array
    {
        $restriction = $this->getById($id);
        
        // Verificar se existem fixed_pairs afetados
        $hasAffectedFixedPairs = $restriction->hasAffectedFixedPairs();
        $affectedCount = $restriction->getAffectedFixedPairsCount();

        $data = [
            'id' => $restriction->id,
            'requester_publisher_id' => $restriction->requester_publisher_id,
            'restricted_publisher_id' => $restriction->restricted_publisher_id,
            'requester_name' => $restriction->requesterPublisher->name,
            'restricted_name' => $restriction->restrictedPublisher->name,
            'full_name' => $restriction->full_name,
            'has_affected_fixed_pairs' => $hasAffectedFixedPairs,
            'affected_fixed_pairs_count' => $affectedCount
        ];

        // Se houver fixed_pairs afetados, retornar aviso
        if ($hasAffectedFixedPairs) {
            $data['affected_fixed_pairs'] = $restriction->getAffectedFixedPairs();
            $data['warning'] = "Esta restrição afeta {$affectedCount} par(es) fixo(s).";
        }

        $restriction->delete();

        $message = $hasAffectedFixedPairs 
            ? "Restrição deletada. {$affectedCount} par(es) fixo(s) foram afetados."
            : "Restrição deletada com sucesso.";

        return [
            'message' => $message,
            'data' => $data
        ];
    }

    /**
     * Buscar uma restrição por ID
     */
    public function getById(int $id): PublisherPairRestriction
    {
        try {
            return PublisherPairRestriction::with([
                'requesterPublisher',
                'restrictedPublisher'
            ])->findOrFail($id);
        } catch (ModelNotFoundException $e) {
            throw new \Exception('Restrição não encontrada.');
        }
    }

    /**
     * Buscar todas as restrições
     */
    public function getAll(): Collection
    {
        return PublisherPairRestriction::with([
            'requesterPublisher',
            'restrictedPublisher'
        ])->orderBy('id')->get();
    }

    /**
     * Buscar restrições por requester
     */
    public function getByRequester(int $publisherId): Collection
    {
        // Verificar se o publisher existe
        $publisher = Publisher::find($publisherId);
        if (!$publisher) {
            throw new \Exception('Publisher não encontrado.');
        }

        return PublisherPairRestriction::with([
            'requesterPublisher',
            'restrictedPublisher'
        ])->forRequester($publisherId)->get();
    }

    /**
     * Buscar restrições por restricted
     */
    public function getByRestricted(int $publisherId): Collection
    {
        // Verificar se o publisher existe
        $publisher = Publisher::find($publisherId);
        if (!$publisher) {
            throw new \Exception('Publisher não encontrado.');
        }

        return PublisherPairRestriction::with([
            'requesterPublisher',
            'restrictedPublisher'
        ])->forRestricted($publisherId)->get();
    }

    /**
     * Buscar restrição por par de publishers
     */
    public function getByPair(int $publisherOneId, int $publisherTwoId): ?PublisherPairRestriction
    {
        return PublisherPairRestriction::forPair($publisherOneId, $publisherTwoId)->first();
    }

    /**
     * Verificar se existe restrição específica
     */
    public function hasRestriction(int $requesterId, int $restrictedId): bool
    {
        return PublisherPairRestriction::hasRestriction($requesterId, $restrictedId);
    }

    /**
     * Verificar se existe restrição em qualquer direção
     */
    public function hasAnyRestriction(int $publisherId1, int $publisherId2): bool
    {
        return PublisherPairRestriction::hasAnyRestriction($publisherId1, $publisherId2);
    }

    /**
     * Buscar publishers que restringem um publisher específico
     */
    public function getPublishersThatRestrictMe(int $publisherId): Collection
    {
        // Verificar se o publisher existe
        $publisher = Publisher::find($publisherId);
        if (!$publisher) {
            throw new \Exception('Publisher não encontrado.');
        }

        return Publisher::whereIn('id', function ($query) use ($publisherId) {
            $query->select('requester_publisher_id')
                  ->from('publisher_pair_restrictions')
                  ->where('restricted_publisher_id', $publisherId);
        })->get();
    }

    /**
     * Buscar publishers restritos por um publisher específico
     */
    public function getRestrictedPublishers(int $publisherId): Collection
    {
        // Verificar se o publisher existe
        $publisher = Publisher::find($publisherId);
        if (!$publisher) {
            throw new \Exception('Publisher não encontrado.');
        }

        return Publisher::whereIn('id', function ($query) use ($publisherId) {
            $query->select('restricted_publisher_id')
                  ->from('publisher_pair_restrictions')
                  ->where('requester_publisher_id', $publisherId);
        })->get();
    }

    /**
     * Obter resumo das restrições de um publisher
     */
    public function getRestrictionsSummary(int $publisherId): array
    {
        // Verificar se o publisher existe
        $publisher = Publisher::find($publisherId);
        if (!$publisher) {
            throw new \Exception('Publisher não encontrado.');
        }

        $restrictionsMade = $this->getByRequester($publisherId);
        $restrictionsReceived = $this->getByRestricted($publisherId);
        $restrictedPublishers = $this->getRestrictedPublishers($publisherId);
        $publishersThatRestrictMe = $this->getPublishersThatRestrictMe($publisherId);

        return [
            'publisher' => [
                'id' => $publisher->id,
                'name' => $publisher->name,
                'is_active' => $publisher->is_active
            ],
            'restrictions_made' => [
                'count' => $restrictionsMade->count(),
                'list' => $restrictionsMade->map(function ($r) {
                    return [
                        'id' => $r->id,
                        'restricted_publisher' => $r->restrictedPublisher->name,
                        'has_affected_fixed_pairs' => $r->hasAffectedFixedPairs()
                    ];
                })
            ],
            'restrictions_received' => [
                'count' => $restrictionsReceived->count(),
                'list' => $restrictionsReceived->map(function ($r) {
                    return [
                        'id' => $r->id,
                        'requester_publisher' => $r->requesterPublisher->name,
                        'has_affected_fixed_pairs' => $r->hasAffectedFixedPairs()
                    ];
                })
            ],
            'restricted_publishers' => $restrictedPublishers->pluck('name')->toArray(),
            'publishers_that_restrict_me' => $publishersThatRestrictMe->pluck('name')->toArray()
        ];
    }

    /**
     * Verificar se uma restrição pode ser deletada
     */
    public function canDelete(int $id): array
    {
        $restriction = $this->getById($id);
        $hasAffectedFixedPairs = $restriction->hasAffectedFixedPairs();
        $affectedCount = $restriction->getAffectedFixedPairsCount();

        return [
            'can_delete' => true,
            'has_affected_fixed_pairs' => $hasAffectedFixedPairs,
            'affected_fixed_pairs_count' => $affectedCount,
            'warning' => $hasAffectedFixedPairs 
                ? "Esta restrição afeta {$affectedCount} par(es) fixo(s)."
                : null
        ];
    }

    /**
     * Buscar todas as restrições com detalhes completos para API
     */
    public function getForApi(): Collection
    {
        return $this->getAll()->map(function ($restriction) {
            return [
                'id' => $restriction->id,
                'requester_publisher' => [
                    'id' => $restriction->requesterPublisher->id,
                    'name' => $restriction->requesterPublisher->name,
                    'is_active' => $restriction->requesterPublisher->is_active
                ],
                'restricted_publisher' => [
                    'id' => $restriction->restrictedPublisher->id,
                    'name' => $restriction->restrictedPublisher->name,
                    'is_active' => $restriction->restrictedPublisher->is_active
                ],
                'full_name' => $restriction->full_name,
                'has_affected_fixed_pairs' => $restriction->hasAffectedFixedPairs(),
                'affected_fixed_pairs_count' => $restriction->getAffectedFixedPairsCount(),
                'created_at' => $restriction->created_at?->format('Y-m-d H:i:s'),
                'updated_at' => $restriction->updated_at?->format('Y-m-d H:i:s')
            ];
        });
    }
}