<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublisherPairRestrictionRequest;
use App\Http\Resources\PublisherPairRestrictionResource;
use App\Services\PublisherPairRestrictionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublisherPairRestrictionController extends Controller
{
    public function __construct(
        private PublisherPairRestrictionService $restrictionService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $requesterId = $request->integer('requester_id', null);
            $restrictedId = $request->integer('restricted_id', null);

            // Buscar dados conforme filtros
            if ($requesterId) {
                $restrictions = $this->restrictionService->getByRequester($requesterId);
            } elseif ($restrictedId) {
                $restrictions = $this->restrictionService->getByRestricted($restrictedId);
            } else {
                $restrictions = $this->restrictionService->getAll();
            }

            return response()->json([
                'success' => true,
                'message' => 'Restrições recuperadas com sucesso.',
                'data' => PublisherPairRestrictionResource::collection($restrictions),
                'meta' => [
                    'total' => $restrictions->count(),
                    'filters' => [
                        'requester_id' => $requesterId,
                        'restricted_id' => $restrictedId
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao recuperar restrições: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PublisherPairRestrictionRequest $request): JsonResponse
    {
        try {
            $restriction = $this->restrictionService->create($request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Restrição criada com sucesso.',
                'data' => new PublisherPairRestrictionResource($restriction)
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar restrição: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id, Request $request): JsonResponse
    {
        try {
            $restriction = $this->restrictionService->getById($id);
            
            $includeAffectedFixedPairs = $request->boolean('include_affected_fixed_pairs', false);
            if ($includeAffectedFixedPairs && $restriction->hasAffectedFixedPairs()) {
                $restriction->load(['affectedFixedPairs']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Restrição recuperada com sucesso.',
                'data' => new PublisherPairRestrictionResource($restriction)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Restrição não encontrada.'
            ], 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            // Verificar se pode deletar antes
            $canDeleteInfo = $this->restrictionService->canDelete($id);
            
            $result = $this->restrictionService->delete($id);

            $response = [
                'success' => true,
                'message' => $result['message'],
                'data' => $result['data']
            ];

            // Adicionar warning se houver fixed_pairs afetados
            if ($canDeleteInfo['has_affected_fixed_pairs']) {
                $response['warning'] = $canDeleteInfo['warning'];
            }

            return response()->json($response);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao deletar restrição: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get restrictions by requester.
     */
    public function getByRequester(int $publisherId): JsonResponse
    {
        try {
            $restrictions = $this->restrictionService->getByRequester($publisherId);

            return response()->json([
                'success' => true,
                'message' => 'Restrições por solicitante recuperadas com sucesso.',
                'data' => PublisherPairRestrictionResource::collection($restrictions),
                'meta' => [
                    'publisher_id' => $publisherId,
                    'total' => $restrictions->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get restrictions by restricted.
     */
    public function getByRestricted(int $publisherId): JsonResponse
    {
        try {
            $restrictions = $this->restrictionService->getByRestricted($publisherId);

            return response()->json([
                'success' => true,
                'message' => 'Restrições por restrito recuperadas com sucesso.',
                'data' => PublisherPairRestrictionResource::collection($restrictions),
                'meta' => [
                    'publisher_id' => $publisherId,
                    'total' => $restrictions->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Check restriction between two publishers.
     */
    public function checkRestriction(int $publisherOneId, int $publisherTwoId): JsonResponse
    {
        try {
            $hasRestriction = $this->restrictionService->hasAnyRestriction($publisherOneId, $publisherTwoId);
            $restriction = $this->restrictionService->getByPair($publisherOneId, $publisherTwoId);

            return response()->json([
                'success' => true,
                'data' => [
                    'publisher_one_id' => $publisherOneId,
                    'publisher_two_id' => $publisherTwoId,
                    'has_restriction' => $hasRestriction,
                    'restriction' => $restriction ? new PublisherPairRestrictionResource($restriction) : null,
                    'message' => $hasRestriction 
                        ? 'Existe restrição entre estes publishers.'
                        : 'Não existe restrição entre estes publishers.'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao verificar restrição: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get restrictions summary for a publisher.
     */
    public function getRestrictionsSummary(int $publisherId): JsonResponse
    {
        try {
            $summary = $this->restrictionService->getRestrictionsSummary($publisherId);

            return response()->json([
                'success' => true,
                'message' => 'Resumo de restrições recuperado com sucesso.',
                'data' => $summary
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get publishers that restrict a publisher.
     */
    public function getPublishersThatRestrictMe(int $publisherId): JsonResponse
    {
        try {
            $publishers = $this->restrictionService->getPublishersThatRestrictMe($publisherId);

            return response()->json([
                'success' => true,
                'message' => 'Publishers que restringem recuperados com sucesso.',
                'data' => [
                    'publisher_id' => $publisherId,
                    'publishers' => $publishers,
                    'total' => $publishers->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Get restricted publishers by a publisher.
     */
    public function getRestrictedPublishers(int $publisherId): JsonResponse
    {
        try {
            $publishers = $this->restrictionService->getRestrictedPublishers($publisherId);

            return response()->json([
                'success' => true,
                'message' => 'Publishers restritos recuperados com sucesso.',
                'data' => [
                    'publisher_id' => $publisherId,
                    'publishers' => $publishers,
                    'total' => $publishers->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Check if a restriction can be deleted.
     */
    public function checkDelete(int $id): JsonResponse
    {
        try {
            $info = $this->restrictionService->canDelete($id);

            return response()->json([
                'success' => true,
                'data' => $info
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Restrição não encontrada.'
            ], 404);
        }
    }
}