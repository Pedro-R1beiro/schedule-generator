<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WeekdayResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Verificar se precisa carregar os time_slots
        $includeTimeSlots = $request->boolean('include_time_slots', false);
        
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'name_pt' => $this->getNameInPortuguese(),
            'display_order' => $this->display_order,
            'is_active' => $this->hasActiveTimeSlots(),
            'is_used' => $this->isUsed(),
            'weekday_time_slots_count' => $this->weekdayTimeSlots()->count(),
            'active_time_slots_count' => $this->weekdayTimeSlots()
                                          ->whereHas('timeSlot', function ($q) {
                                              $q->where('is_active', true);
                                          })
                                          ->count(),
            'relationships' => $this->getRelationshipsCountAttribute(),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];

        // Incluir time_slots se solicitado
        if ($includeTimeSlots) {
            $data['time_slots'] = TimeSlotResource::collection(
                $this->whenLoaded('timeSlots', function () {
                    return $this->timeSlots->where('is_active', true);
                })
            );
        }

        return $data;
    }

    /**
     * Customize the outgoing response for the resource.
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'version' => '1.0',
                'resource' => 'Weekday'
            ]
        ];
    }
}