<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FixedPairResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'publisher_one' => [
                'id' => $this->publisherOne->id ?? null,
                'name' => $this->publisherOne->name ?? null,
                'is_active' => $this->publisherOne->is_active ?? null,
                'is_pioneer' => $this->publisherOne->is_pioneer ?? null,
                'gender' => $this->publisherOne->gender ?? null
            ],
            'publisher_two' => [
                'id' => $this->publisherTwo->id ?? null,
                'name' => $this->publisherTwo->name ?? null,
                'is_active' => $this->publisherTwo->is_active ?? null,
                'is_pioneer' => $this->publisherTwo->is_pioneer ?? null,
                'gender' => $this->publisherTwo->gender ?? null
            ],
            'weekday_time_slot_id' => $this->weekday_time_slot_id,
            'weekday' => [
                'id' => $this->weekdayTimeSlot->weekday->id ?? null,
                'name' => $this->weekdayTimeSlot->weekday->name ?? null,
                'display_order' => $this->weekdayTimeSlot->weekday->display_order ?? null
            ],
            'time_slot' => [
                'id' => $this->weekdayTimeSlot->timeSlot->id ?? null,
                'name' => $this->weekdayTimeSlot->timeSlot->name ?? null,
                'start_time' => $this->weekdayTimeSlot->timeSlot->start_time ?? null,
                'end_time' => $this->weekdayTimeSlot->timeSlot->end_time ?? null,
                'is_active' => $this->weekdayTimeSlot->timeSlot->is_active ?? null
            ],
            'has_restrictions' => $this->hasRestrictions(),
            'full_name' => $this->full_name ?? null,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Customize the outgoing response for the resource.
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'version' => '1.0',
                'resource' => 'FixedPair'
            ]
        ];
    }
}