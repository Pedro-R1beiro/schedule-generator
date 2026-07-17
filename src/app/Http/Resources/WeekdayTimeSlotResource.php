<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WeekdayTimeSlotResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $includeFixedPairs = $request->boolean('include_fixed_pairs', false);

        $data = [
            'id' => $this->id,
            'weekday' => [
                'id' => $this->weekday->id ?? null,
                'name' => $this->weekday->name ?? null,
                'name_pt' => $this->weekday ? $this->weekday->getNameInPortuguese() : null,
                'display_order' => $this->weekday->display_order ?? null
            ],
            'time_slot' => [
                'id' => $this->timeSlot->id ?? null,
                'name' => $this->timeSlot->name ?? null,
                'start_time' => $this->timeSlot->start_time ?? null,
                'end_time' => $this->timeSlot->end_time ?? null,
                'is_active' => $this->timeSlot->is_active ?? null
            ],
            'full_name' => $this->full_name ?? null,
            'full_name_pt' => $this->full_name_pt ?? null,
            'fixed_pairs_count' => $this->getFixedPairsCount(),
            'has_fixed_pairs' => $this->hasFixedPairs(),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s')
        ];

        // Incluir fixed_pairs se solicitado
        if ($includeFixedPairs && $this->hasFixedPairs()) {
            $data['fixed_pairs'] = FixedPairResource::collection(
                $this->whenLoaded('fixedPairs')
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
                'resource' => 'WeekdayTimeSlot'
            ]
        ];
    }
}