<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PublisherRequest;
use App\Http\Resources\PublisherResource;
use App\Services\PublisherService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PublisherController extends Controller
{
    public function __construct(
        private PublisherService $publisherService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $gender = $request->input('gender', null);
            $mode = $request->input('mode', null);
            $onlyActive = $request->boolean('only_active', false);
            $onlyPioneers = $request->boolean('only_pioneers', false);

            // Buscar dados conforme filtros
            if ($gender) {
                $publishers = $this->publisherService->getByGender(strtoupper($gender));
            } elseif ($mode) {
                $publishers = $this->publisherService->getByPreferenceMode(strtoupper($mode));
            } elseif ($onlyPioneers) {
                $publishers = $this->publisherService->getPioneers();
            } elseif ($onlyActive) {
                $publishers = $this->publisherService->getActive();
            } else {
                $publishers = $this->publisherService->getAll();
            }

            return response()->json([
                'success' => true,
                'message' => 'Publishers recuperados com sucesso.',
                'data' => PublisherResource::collection($publishers),
                'meta' => [
                    'total' => $publishers->count(),
                    'filters' => [
                        'gender' => $gender,
                        'mode' => $mode,
                        'only_active' => $onlyActive,
                        'only_pioneers' => $onlyPioneers
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao recuperar publishers: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PublisherRequest $request): JsonResponse
    {
        try {
            $publisher = $this->publisherService->create($request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Publisher criado com sucesso.',
                'data' => new PublisherResource($publisher)
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
                'message' => 'Erro ao criar publisher: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id, Request $request): JsonResponse
    {
        try {
            $publisher = $this->publisherService->getById($id);
            
            $includeRelationships = $request->boolean('include_relationships', false);
            $includeSummary = $request->boolean('include_summary', false);

            return response()->json([
                'success' => true,
                'message' => 'Publisher recuperado com sucesso.',
                'data' => new PublisherResource(
                    $publisher->load([
                        'fixedPairsAsOne',
                        'fixedPairsAsTwo',
                        'requestedRestrictions',
                        'restrictedByOthers',
                        'requestedPreferences',
                        'preferredByOthers'
                    ])
                )
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Publisher não encontrado.'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PublisherRequest $request, int $id): JsonResponse
    {
        try {
            $publisher = $this->publisherService->update($id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Publisher atualizado com sucesso.',
                'data' => new PublisherResource($publisher)
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar publisher: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $result = $this->publisherService->delete($id);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['data']
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao desativar publisher: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activate a publisher.
     */
    public function activate(int $id): JsonResponse
    {
        try {
            $publisher = $this->publisherService->activate($id);

            return response()->json([
                'success' => true,
                'message' => 'Publisher ativado com sucesso.',
                'data' => new PublisherResource($publisher)
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao ativar publisher: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deactivate a publisher.
     */
    public function deactivate(int $id): JsonResponse
    {
        try {
            $result = $this->publisherService->delete($id);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['data']
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao desativar publisher: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get active publishers.
     */
    public function getActive(): JsonResponse
    {
        try {
            $publishers = $this->publisherService->getActive();

            return response()->json([
                'success' => true,
                'message' => 'Publishers ativos recuperados com sucesso.',
                'data' => PublisherResource::collection($publishers),
                'meta' => [
                    'total' => $publishers->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao recuperar publishers ativos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pioneer publishers.
     */
    public function getPioneers(): JsonResponse
    {
        try {
            $publishers = $this->publisherService->getPioneers();

            return response()->json([
                'success' => true,
                'message' => 'Publishers pioneiros recuperados com sucesso.',
                'data' => PublisherResource::collection($publishers),
                'meta' => [
                    'total' => $publishers->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao recuperar publishers pioneiros: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get publisher summary.
     */
    public function getSummary(int $id): JsonResponse
    {
        try {
            $summary = $this->publisherService->getPublisherSummary($id);

            return response()->json([
                'success' => true,
                'message' => 'Resumo do publisher recuperado com sucesso.',
                'data' => $summary
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Publisher não encontrado.'
            ], 404);
        }
    }

    /**
     * Get publisher restrictions.
     */
    public function getRestrictions(int $id): JsonResponse
    {
        try {
            $restrictions = $this->publisherService->getWithRestrictions($id);

            return response()->json([
                'success' => true,
                'message' => 'Restrições do publisher recuperadas com sucesso.',
                'data' => $restrictions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Publisher não encontrado.'
            ], 404);
        }
    }

    /**
     * Get publisher preferences.
     */
    public function getPreferences(int $id): JsonResponse
    {
        try {
            $preferences = $this->publisherService->getWithPreferences($id);

            return response()->json([
                'success' => true,
                'message' => 'Preferências do publisher recuperadas com sucesso.',
                'data' => $preferences
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Publisher não encontrado.'
            ], 404);
        }
    }

    /**
     * Get publisher fixed pairs.
     */
    public function getFixedPairs(int $id): JsonResponse
    {
        try {
            $fixedPairs = $this->publisherService->getWithFixedPairs($id);

            return response()->json([
                'success' => true,
                'message' => 'Pares fixos do publisher recuperados com sucesso.',
                'data' => $fixedPairs
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Publisher não encontrado.'
            ], 404);
        }
    }

    /**
     * Get publisher statistics.
     */
    public function getStatistics(): JsonResponse
    {
        try {
            $statistics = $this->publisherService->getStatistics();

            return response()->json([
                'success' => true,
                'message' => 'Estatísticas de publishers recuperadas com sucesso.',
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
     * Get available publishers.
     */
    public function getAvailable(): JsonResponse
    {
        try {
            $publishers = $this->publisherService->getAvailablePublishers();

            return response()->json([
                'success' => true,
                'message' => 'Publishers disponíveis recuperados com sucesso.',
                'data' => PublisherResource::collection($publishers),
                'meta' => [
                    'total' => $publishers->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao recuperar publishers disponíveis: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check publisher availability.
     */
    public function checkAvailability(int $id): JsonResponse
    {
        try {
            $isAvailable = $this->publisherService->checkAvailability($id);
            $publisher = $this->publisherService->getById($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $id,
                    'name' => $publisher->name,
                    'is_available' => $isAvailable,
                    'is_active' => $publisher->is_active,
                    'has_restrictions' => $publisher->requestedRestrictions()->exists() || 
                                         $publisher->restrictedByOthers()->exists()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Publisher não encontrado.'
            ], 404);
        }
    }
}