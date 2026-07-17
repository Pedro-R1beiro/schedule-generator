<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\WeekdayTimeSlotRequest;
use App\Http\Resources\WeekdayTimeSlotResource;
use App\Services\WeekdayTimeSlotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WeekdayTimeSlotController extends Controller
{
    public function __construct(
        private WeekdayTimeSlotService $weekdayTimeSlotService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $includeFixedPairs = $request->boolean('include_fixed_pairs', false);
            $onlyWithFixedPairs = $request->boolean('only_with_fixed_pairs', false);
            $onlyWithoutFixedPairs = $request->boolean('only_without_fixed_pairs', false);

            // Buscar dados conforme filtros
            if ($onlyWithFixedPairs) {
                $weekdayTimeSlots = $this->weekdayTimeSlotService->getWithFixedPairs();
            } elseif ($onlyWithoutFixedPairs) {
                $weekdayTimeSlots = $this->weekdayTimeSlotService->getWithoutFixedPairs();
            } else {
                $weekdayTimeSlots = $this->weekdayTimeSlotService->getAll();
            }

            // Carregar fixed_pairs se solicitado
            if ($includeFixedPairs) {
                $weekdayTimeSlots->load('fixedPairs');
            }

            return response()->json([
                'success' => true,
                'message' => 'Relacionamentos dia-horário recuperados com sucesso.',
                'data' => WeekdayTimeSlotResource::collection($weekdayTimeSlots),
                'meta' => [
                    'total' => $weekdayTimeSlots->count(),
                    'filters' => [
                        'include_fixed_pairs' => $includeFixedPairs,
                        'only_with_fixed_pairs' => $onlyWithFixedPairs,
                        'only_without_fixed_pairs' => $onlyWithoutFixedPairs
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao recuperar relacionamentos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(WeekdayTimeSlotRequest $request): JsonResponse
    {
        try {
            $weekdayTimeSlot = $this->weekdayTimeSlotService->create($request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Relacionamento dia-horário criado com sucesso.',
                'data' => new WeekdayTimeSlotResource($weekdayTimeSlot)
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
                'message' => 'Erro ao criar relacionamento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id, Request $request): JsonResponse
    {
        try {
            $weekdayTimeSlot = $this->weekdayTimeSlotService->getById($id);
            
            $includeFixedPairs = $request->boolean('include_fixed_pairs', false);
            if ($includeFixedPairs) {
                $weekdayTimeSlot->load('fixedPairs');
            }

            return response()->json([
                'success' => true,
                'message' => 'Relacionamento dia-horário recuperado com sucesso.',
                'data' => new WeekdayTimeSlotResource($weekdayTimeSlot)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Relacionamento dia-horário não encontrado.'
            ], 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $result = $this->weekdayTimeSlotService->delete($id);
            
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
                'message' => 'Erro ao deletar relacionamento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get weekday_time_slots by weekday.
     */
    public function getByWeekday(int $weekdayId): JsonResponse
    {
        try {
            $weekdayTimeSlots = $this->weekdayTimeSlotService->getByWeekday($weekdayId);

            return response()->json([
                'success' => true,
                'message' => 'Relacionamentos por dia recuperados com sucesso.',
                'data' => WeekdayTimeSlotResource::collection($weekdayTimeSlots),
                'meta' => [
                    'weekday_id' => $weekdayId,
                    'total' => $weekdayTimeSlots->count()
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
     * Get weekday_time_slots by time_slot.
     */
    public function getByTimeSlot(int $timeSlotId): JsonResponse
    {
        try {
            $weekdayTimeSlots = $this->weekdayTimeSlotService->getByTimeSlot($timeSlotId);

            return response()->json([
                'success' => true,
                'message' => 'Relacionamentos por horário recuperados com sucesso.',
                'data' => WeekdayTimeSlotResource::collection($weekdayTimeSlots),
                'meta' => [
                    'time_slot_id' => $timeSlotId,
                    'total' => $weekdayTimeSlots->count()
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
     * Get available time slots for a weekday.
     */
    public function getAvailableTimeSlots(int $weekdayId): JsonResponse
    {
        try {
            $timeSlots = $this->weekdayTimeSlotService->getAvailableTimeSlots($weekdayId);

            return response()->json([
                'success' => true,
                'message' => 'Horários disponíveis recuperados com sucesso.',
                'data' => [
                    'weekday_id' => $weekdayId,
                    'available_time_slots' => $timeSlots,
                    'total' => $timeSlots->count()
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
     * Check if a weekday_time_slot can be deleted.
     */
    public function checkDelete(int $id): JsonResponse
    {
        try {
            $weekdayTimeSlot = $this->weekdayTimeSlotService->getById($id);
            $hasFixedPairs = $weekdayTimeSlot->hasFixedPairs();
            $fixedPairsCount = $weekdayTimeSlot->getFixedPairsCount();

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $id,
                    'can_delete' => !$hasFixedPairs,
                    'has_fixed_pairs' => $hasFixedPairs,
                    'fixed_pairs_count' => $fixedPairsCount,
                    'full_name' => $weekdayTimeSlot->full_name,
                    'message' => $hasFixedPairs 
                        ? "Não é possível deletar pois existem {$fixedPairsCount} pares fixos associados."
                        : 'Pode ser deletado com segurança.'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Relacionamento dia-horário não encontrado.'
            ], 404);
        }
    }

    /**
     * Check if a pair exists.
     */
    public function checkPair(Request $request): JsonResponse
    {
        try {
            $weekdayId = (int) $request->input('weekday_id');
            $timeSlotId = (int) $request->input('time_slot_id');

            if (!$weekdayId || !$timeSlotId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Os parâmetros weekday_id e time_slot_id são obrigatórios.'
                ], 422);
            }

            $exists = $this->weekdayTimeSlotService->pairExists($weekdayId, $timeSlotId);

            return response()->json([
                'success' => true,
                'data' => [
                    'weekday_id' => $weekdayId,
                    'time_slot_id' => $timeSlotId,
                    'exists' => $exists
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