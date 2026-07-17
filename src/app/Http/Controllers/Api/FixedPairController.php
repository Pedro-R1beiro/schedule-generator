<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\FixedPairRequest;
use App\Http\Resources\FixedPairResource;
use App\Services\FixedPairService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FixedPairController extends Controller
{
    public function __construct(
        private FixedPairService $fixedPairService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $weekdayTimeSlotId = $request->integer('weekday_time_slot_id', null);
            $publisherId = $request->integer('publisher_id', null);

            // Buscar dados conforme filtros
            if ($weekdayTimeSlotId) {
                $fixedPairs = $this->fixedPairService->getByWeekdayTimeSlot($weekdayTimeSlotId);
            } elseif ($publisherId) {
                $fixedPairs = $this->fixedPairService->getByPublisher($publisherId);
            } else {
                $fixedPairs = $this->fixedPairService->getAll();
            }

            return response()->json([
                'success' => true,
                'message' => 'Pares fixos recuperados com sucesso.',
                'data' => FixedPairResource::collection($fixedPairs),
                'meta' => [
                    'total' => $fixedPairs->count(),
                    'filters' => [
                        'weekday_time_slot_id' => $weekdayTimeSlotId,
                        'publisher_id' => $publisherId
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao recuperar pares fixos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(FixedPairRequest $request): JsonResponse
    {
        try {
            $fixedPair = $this->fixedPairService->create($request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Par fixo criado com sucesso.',
                'data' => new FixedPairResource($fixedPair)
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
                'message' => 'Erro ao criar par fixo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $fixedPair = $this->fixedPairService->getById($id);

            return response()->json([
                'success' => true,
                'message' => 'Par fixo recuperado com sucesso.',
                'data' => new FixedPairResource($fixedPair)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Par fixo não encontrado.'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(FixedPairRequest $request, int $id): JsonResponse
    {
        try {
            $fixedPair = $this->fixedPairService->update($id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Par fixo atualizado com sucesso.',
                'data' => new FixedPairResource($fixedPair)
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
                'message' => 'Erro ao atualizar par fixo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $result = $this->fixedPairService->delete($id);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result['data']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao deletar par fixo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get fixed pairs by weekday_time_slot.
     */
    public function getByWeekdayTimeSlot(int $weekdayTimeSlotId): JsonResponse
    {
        try {
            $fixedPairs = $this->fixedPairService->getByWeekdayTimeSlot($weekdayTimeSlotId);

            return response()->json([
                'success' => true,
                'message' => 'Pares fixos por dia/horário recuperados com sucesso.',
                'data' => FixedPairResource::collection($fixedPairs),
                'meta' => [
                    'weekday_time_slot_id' => $weekdayTimeSlotId,
                    'total' => $fixedPairs->count()
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
     * Get fixed pairs by publisher.
     */
    public function getByPublisher(int $publisherId): JsonResponse
    {
        try {
            $fixedPairs = $this->fixedPairService->getByPublisher($publisherId);

            return response()->json([
                'success' => true,
                'message' => 'Pares fixos por publisher recuperados com sucesso.',
                'data' => FixedPairResource::collection($fixedPairs),
                'meta' => [
                    'publisher_id' => $publisherId,
                    'total' => $fixedPairs->count()
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
     * Get available publishers for a slot.
     */
    public function getAvailablePublishers(int $weekdayTimeSlotId): JsonResponse
    {
        try {
            $publishers = $this->fixedPairService->getAvailablePublishersForSlot($weekdayTimeSlotId);

            return response()->json([
                'success' => true,
                'message' => 'Publishers disponíveis recuperados com sucesso.',
                'data' => [
                    'weekday_time_slot_id' => $weekdayTimeSlotId,
                    'available_publishers' => $publishers,
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
     * Check restrictions between two publishers.
     */
    public function checkRestrictions(Request $request): JsonResponse
    {
        try {
            $publisherOneId = (int) $request->input('publisher_one_id');
            $publisherTwoId = (int) $request->input('publisher_two_id');

            if (!$publisherOneId || !$publisherTwoId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Os parâmetros publisher_one_id e publisher_two_id são obrigatórios.'
                ], 422);
            }

            $hasRestriction = $this->fixedPairService->checkRestrictions($publisherOneId, $publisherTwoId);

            return response()->json([
                'success' => true,
                'data' => [
                    'publisher_one_id' => $publisherOneId,
                    'publisher_two_id' => $publisherTwoId,
                    'has_restriction' => $hasRestriction,
                    'message' => $hasRestriction 
                        ? 'Existe restrição entre estes publishers.'
                        : 'Não existe restrição entre estes publishers.'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao verificar restrições: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if a pair exists.
     */
    public function checkPair(Request $request): JsonResponse
    {
        try {
            $weekdayTimeSlotId = (int) $request->input('weekday_time_slot_id');
            $publisherOneId = (int) $request->input('publisher_one_id');
            $publisherTwoId = (int) $request->input('publisher_two_id');

            if (!$weekdayTimeSlotId || !$publisherOneId || !$publisherTwoId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Os parâmetros weekday_time_slot_id, publisher_one_id e publisher_two_id são obrigatórios.'
                ], 422);
            }

            $exists = $this->fixedPairService->checkExistingPair(
                $weekdayTimeSlotId,
                $publisherOneId,
                $publisherTwoId
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'weekday_time_slot_id' => $weekdayTimeSlotId,
                    'publisher_one_id' => $publisherOneId,
                    'publisher_two_id' => $publisherTwoId,
                    'exists' => $exists,
                    'message' => $exists 
                        ? 'Já existe um par fixo para este dia/horário com estes publishers.'
                        : 'Não existe par fixo para este dia/horário com estes publishers.'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao verificar par: ' . $e->getMessage()
            ], 500);
        }
    }
}