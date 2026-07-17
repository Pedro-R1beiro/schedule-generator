<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\WeekdayResource;
use App\Services\WeekdayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WeekdayController extends Controller
{
    public function __construct(
        private WeekdayService $weekdayService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $includeTimeSlots = $request->boolean('include_time_slots', false);
            $onlyActive = $request->boolean('only_active', false);

            // Buscar dados
            if ($onlyActive) {
                $weekdays = $this->weekdayService->getActive();
            } else {
                $weekdays = $this->weekdayService->getAll();
            }

            // Carregar relacionamentos se solicitado
            if ($includeTimeSlots) {
                $weekdays->load(['timeSlots' => function ($query) {
                    $query->where('is_active', true)->orderBy('start_time');
                }]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Dias da semana recuperados com sucesso.',
                'data' => WeekdayResource::collection($weekdays),
                'meta' => [
                    'total' => $weekdays->count(),
                    'only_active' => $onlyActive,
                    'include_time_slots' => $includeTimeSlots
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao recuperar dias da semana: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id, Request $request): JsonResponse
    {
        try {
            $weekday = $this->weekdayService->getById($id);
            
            $includeTimeSlots = $request->boolean('include_time_slots', false);
            if ($includeTimeSlots) {
                $weekday->load(['timeSlots' => function ($query) {
                    $query->where('is_active', true)->orderBy('start_time');
                }]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Dia da semana recuperado com sucesso.',
                'data' => new WeekdayResource($weekday)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dia da semana não encontrado.'
            ], 404);
        }
    }

    /**
     * Get active weekdays with time slots.
     */
    public function active(Request $request): JsonResponse
    {
        try {
            $includeTimeSlots = $request->boolean('include_time_slots', true);
            
            $weekdays = $this->weekdayService->getActive();
            
            if ($includeTimeSlots) {
                $weekdays->load(['timeSlots' => function ($query) {
                    $query->where('is_active', true)->orderBy('start_time');
                }]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Dias ativos recuperados com sucesso.',
                'data' => WeekdayResource::collection($weekdays),
                'meta' => [
                    'total' => $weekdays->count()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao recuperar dias ativos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get weekdays with formatted time slots.
     */
    public function formatted(): JsonResponse
    {
        try {
            $data = $this->weekdayService->getFormattedWeekdays();

            return response()->json([
                'success' => true,
                'message' => 'Dias da semana formatados recuperados com sucesso.',
                'data' => $data,
                'meta' => [
                    'total' => count($data)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao recuperar dias formatados: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get weekday by name.
     */
    public function byName(string $name): JsonResponse
    {
        try {
            $weekday = $this->weekdayService->getByName($name);
            
            if (!$weekday) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dia da semana não encontrado com o nome: ' . $name
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Dia da semana recuperado com sucesso.',
                'data' => new WeekdayResource($weekday)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar dia da semana: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if a weekday is used.
     */
    public function checkUsed(int $id): JsonResponse
    {
        try {
            $isUsed = $this->weekdayService->isUsed($id);
            $weekday = $this->weekdayService->getById($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $id,
                    'name' => $weekday->name,
                    'is_used' => $isUsed,
                    'relationships' => $weekday->getRelationshipsCountAttribute()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Dia da semana não encontrado.'
            ], 404);
        }
    }

    /**
     * Get weekdays with counts.
     */
    public function withCounts(): JsonResponse
    {
        try {
            $weekdays = $this->weekdayService->getWithCounts();

            return response()->json([
                'success' => true,
                'message' => 'Dias da semana com contagens recuperados com sucesso.',
                'data' => WeekdayResource::collection($weekdays)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao recuperar dias com contagens: ' . $e->getMessage()
            ], 500);
        }
    }
}