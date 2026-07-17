<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublisherPairRestrictionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $includeAffectedFixedPairs = $request->boolean('include_affected_fixed_pairs', false);

        $data = [
            'id' => $this->id,
            'requester_publisher' => [
                'id' => $this->requesterPublisher->id ?? null,
                'name' => $this->requesterPublisher->name ?? null,
                'is_active' => $this->requesterPublisher->is_active ?? null
            ],
            'restricted_publisher' => [
                'id' => $this->restrictedPublisher->id ?? null,
                'name' => $this->restrictedPublisher->name ?? null,
                'is_active' => $this->restrictedPublisher->is_active ?? null
            ],
            'full_name' => $this->full_name ?? null,
            'has_affected_fixed_pairs' => $this->hasAffectedFixedPairs(),
            'affected_fixed_pairs_count' => $this->getAffectedFixedPairsCount(),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s')
        ];

        // Incluir fixed_pairs afetados se solicitado
        if ($includeAffectedFixedPairs && $this->hasAffectedFixedPairs()) {
            $data['affected_fixed_pairs'] = FixedPairResource::collection(
                $this->getAffectedFixedPairs()
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
                'resource' => 'PublisherPairRestriction'
            ]
        ];
    }
}