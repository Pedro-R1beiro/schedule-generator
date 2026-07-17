<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublisherPairPreferenceRequest;
use App\Http\Resources\PublisherPairPreferenceResource;
use App\Services\PublisherPairPreferenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublisherPairPreferenceController extends Controller
{
    public function __construct(
        private PublisherPairPreferenceService $preferenceService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $requesterId = $request->integer('requester_id', null);
            $preferredId = $request->integer('preferred_id', null);
            $mode = $request->input('mode', null);

            // Buscar dados conforme filtros
            if ($requesterId) {
                $preferences = $this->preferenceService->getByRequester($requesterId);
            } elseif ($preferredId) {
                $preferences = $this->preferenceService->getByPreferred($preferredId);
            } elseif ($mode) {
                $preferences = $this->preferenceService->getPreferencesByMode(strtoupper($mode));
            } else {
                $preferences = $this->preferenceService->getAll();
            }

            return response()->json([
                'success' => true,
                'message' => 'Preferências recuperadas com sucesso.',
                'data' => PublisherPairPreferenceResource::collection($preferences),
                'meta' => [
                    'total' => $preferences->count(),
                    'filters' => [
                        'requester_id' => $requesterId,
                        'preferred_id' => $preferredId,
                        'mode' => $mode
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao recuperar preferências: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PublisherPairPreferenceRequest $request): JsonResponse
    {
        try {
            $preference = $this->preferenceService->create($request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Preferência criada com sucesso.',
                'data' => new PublisherPairPreferenceResource($preference),
                'meta' => [
                    'mode_info' => $preference->mode_info ?? null
                ]
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
                'message' => 'Erro ao criar preferência: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $preference = $this->preferenceService->getById($id);

            return response()->json([
                'success' => true,
                'message' => 'Preferência recuperada com sucesso.',
                'data' => new PublisherPairPreferenceResource($preference)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Preferência não encontrada.'
            ], 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $result = $this->preferenceService->delete($id);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['data']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao deletar preferência: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get preferences by requester.
     */
    public function getByRequester(int $publisherId): JsonResponse
    {
        try {
            $preferences = $this->preferenceService->getByRequester($publisherId);

            return response()->json([
                'success' => true,
                'message' => 'Preferências por solicitante recuperadas com sucesso.',
                'data' => PublisherPairPreferenceResource::collection($preferences),
                'meta' => [
                    'publisher_id' => $publisherId,
                    'total' => $preferences->count()
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
     * Get preferences by preferred.
     */
    public function getByPreferred(int $publisherId): JsonResponse
    {
        try {
            $preferences = $this->preferenceService->getByPreferred($publisherId);

            return response()->json([
                'success' => true,
                'message' => 'Preferências por preferido recuperadas com sucesso.',
                'data' => PublisherPairPreferenceResource::collection($preferences),
                'meta' => [
                    'publisher_id' => $publisherId,
                    'total' => $preferences->count()
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
     * Check preference between two publishers.
     */
    public function checkPreference(int $publisherOneId, int $publisherTwoId): JsonResponse
    {
        try {
            $hasPreference = $this->preferenceService->hasAnyPreference($publisherOneId, $publisherTwoId);
            $preference = $this->preferenceService->getByPair($publisherOneId, $publisherTwoId);

            return response()->json([
                'success' => true,
                'data' => [
                    'publisher_one_id' => $publisherOneId,
                    'publisher_two_id' => $publisherTwoId,
                    'has_preference' => $hasPreference,
                    'preference' => $preference ? new PublisherPairPreferenceResource($preference) : null,
                    'message' => $hasPreference 
                        ? 'Existe preferência entre estes publishers.'
                        : 'Não existe preferência entre estes publishers.'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao verificar preferência: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get preferences summary for a publisher.
     */
    public function getPreferencesSummary(int $publisherId): JsonResponse
    {
        try {
            $summary = $this->preferenceService->getPreferencesSummary($publisherId);

            return response()->json([
                'success' => true,
                'message' => 'Resumo de preferências recuperado com sucesso.',
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
     * Get preferences by mode.
     */
    public function getByMode(string $mode): JsonResponse
    {
        try {
            $preferences = $this->preferenceService->getPreferencesByMode(strtoupper($mode));

            return response()->json([
                'success' => true,
                'message' => "Preferências com modo {$mode} recuperadas com sucesso.",
                'data' => PublisherPairPreferenceResource::collection($preferences),
                'meta' => [
                    'mode' => strtoupper($mode),
                    'total' => $preferences->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    /**
     * Get publishers that prefer a publisher.
     */
    public function getPublishersThatPreferMe(int $publisherId): JsonResponse
    {
        try {
            $publishers = $this->preferenceService->getPublishersThatPreferMe($publisherId);

            return response()->json([
                'success' => true,
                'message' => 'Publishers que preferem recuperados com sucesso.',
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
     * Get preferred publishers by a publisher.
     */
    public function getPreferredPublishers(int $publisherId): JsonResponse
    {
        try {
            $publishers = $this->preferenceService->getPreferredPublishers($publisherId);

            return response()->json([
                'success' => true,
                'message' => 'Publishers preferidos recuperados com sucesso.',
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
     * Get preferences statistics.
     */
    public function getStatistics(): JsonResponse
    {
        try {
            $statistics = $this->preferenceService->getStatistics();

            return response()->json([
                'success' => true,
                'message' => 'Estatísticas de preferências recuperadas com sucesso.',
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao recuperar estatísticas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if a preference can be deleted.
     */
    public function checkDelete(int $id): JsonResponse
    {
        try {
            $info = $this->preferenceService->canDelete($id);

            return response()->json([
                'success' => true,
                'data' => $info
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Preferência não encontrada.'
            ], 404);
        }
    }
}