<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublisherPairPreferenceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $modes = [
            'ONLY' => 'Somente',
            'PRIORITY' => 'Prioridade'
        ];

        return [
            'id' => $this->id,
            'requester_publisher' => [
                'id' => $this->requesterPublisher->id ?? null,
                'name' => $this->requesterPublisher->name ?? null,
                'is_active' => $this->requesterPublisher->is_active ?? null,
                'pairing_preference_mode' => $this->requesterPublisher->pairing_preference_mode ?? null,
                'mode_label' => $modes[$this->requesterPublisher->pairing_preference_mode ?? ''] ?? 'Desconhecido'
            ],
            'preferred_publisher' => [
                'id' => $this->preferredPublisher->id ?? null,
                'name' => $this->preferredPublisher->name ?? null,
                'is_active' => $this->preferredPublisher->is_active ?? null,
                'pairing_preference_mode' => $this->preferredPublisher->pairing_preference_mode ?? null,
                'mode_label' => $modes[$this->preferredPublisher->pairing_preference_mode ?? ''] ?? 'Desconhecido'
            ],
            'full_name' => $this->full_name ?? null,
            'full_name_with_mode' => $this->full_name_with_mode ?? null,
            'requester_mode_label' => $this->getRequesterModeLabel(),
            'requester_is_only' => $this->requesterIsOnlyMode(),
            'requester_is_priority' => $this->requesterIsPriorityMode(),
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
                'resource' => 'PublisherPairPreference'
            ]
        ];
    }
}