<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TimeSlotRequest;
use App\Http\Resources\TimeSlotResource;
use App\Models\TimeSlot;
use App\Services\TimeSlotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class TimeSlotController extends Controller
{
    public function __construct(
        private TimeSlotService $timeSlotService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = TimeSlot::query()->ordered();

        // Filtrar por ativo
        if ($request->has('active')) {
            if ($request->boolean('active')) {
                $query->active();
            } else {
                $query->inactive();
            }
        }

        // Buscar com relacionamentos
        if ($request->boolean('with_relationships')) {
            $query->with(['weekdayTimeSlots', 'fixedPairs']);
        }

        $timeSlots = $query->paginate($request->get('per_page', 15));

        return TimeSlotResource::collection($timeSlots);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(TimeSlotRequest $request): JsonResponse
    {
        try {
            $timeSlot = $this->timeSlotService->create($request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Horário criado com sucesso.',
                'data' => new TimeSlotResource($timeSlot)
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
                'message' => 'Erro ao criar horário: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $timeSlot = $this->timeSlotService->getTimeSlotById($id);
            
            return response()->json([
                'success' => true,
                'data' => new TimeSlotResource($timeSlot)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Horário não encontrado.'
            ], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TimeSlotRequest $request, int $id): JsonResponse
    {
        try {
            $timeSlot = $this->timeSlotService->update($id, $request->validated());
            
            return response()->json([
                'success' => true,
                'message' => 'Horário atualizado com sucesso.',
                'data' => new TimeSlotResource($timeSlot)
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
                'message' => 'Erro ao atualizar horário: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $result = $this->timeSlotService->delete($id);
            
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'id' => $result['id'],
                    'action' => $result['action']
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao deletar horário: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Force delete a time slot (remove all relationships).
     */
    public function forceDestroy(int $id): JsonResponse
    {
        try {
            $result = $this->timeSlotService->forceDelete($id);
            
            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => [
                    'id' => $result['id'],
                    'action' => $result['action']
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao deletar horário permanentemente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activate a time slot.
     */
    public function activate(int $id): JsonResponse
    {
        try {
            $timeSlot = $this->timeSlotService->activate($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Horário ativado com sucesso.',
                'data' => new TimeSlotResource($timeSlot)
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
                'message' => 'Erro ao ativar horário: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Deactivate a time slot.
     */
    public function deactivate(int $id): JsonResponse
    {
        try {
            $timeSlot = $this->timeSlotService->deactivate($id);
            
            return response()->json([
                'success' => true,
                'message' => 'Horário desativado com sucesso.',
                'data' => new TimeSlotResource($timeSlot)
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
                'message' => 'Erro ao desativar horário: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if a time slot can be deleted.
     */
    public function checkDelete(int $id): JsonResponse
    {
        try {
            $canDelete = $this->timeSlotService->canDelete($id);
            $timeSlot = $this->timeSlotService->getTimeSlotById($id);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'can_delete' => $canDelete,
                    'has_relationships' => !$canDelete,
                    'relationships' => $timeSlot->relationships_summary,
                    'is_active' => $timeSlot->is_active
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Horário não encontrado.'
            ], 404);
        }
    }

    /**
     * Get available time slots (not overlapping).
     */
    public function available(Request $request): AnonymousResourceCollection
    {
        $query = TimeSlot::active()->ordered();

        // Filtrar por horário específico
        if ($request->has('start_time') && $request->has('end_time')) {
            $startTime = $request->input('start_time');
            $endTime = $request->input('end_time');
            
            // Buscar horários que não sobrepõem
            $query->where(function ($q) use ($startTime, $endTime) {
                $q->where('end_time', '<=', $startTime)
                  ->orWhere('start_time', '>=', $endTime);
            });
        }

        $timeSlots = $query->paginate($request->get('per_page', 15));

        return TimeSlotResource::collection($timeSlots);
    }
}