<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublisherResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $includeRelationships = $request->boolean('include_relationships', false);
        $includeSummary = $request->boolean('include_summary', false);

        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'is_active' => $this->is_active,
            'is_active_label' => $this->getActiveLabel(),
            'is_manual' => $this->is_manual,
            'is_manual_label' => $this->is_manual ? 'Manual' : 'Automático',
            'monthly_limit' => $this->monthly_limit,
            'weekly_limit' => $this->weekly_limit,
            'is_pioneer' => $this->is_pioneer,
            'is_pioneer_label' => $this->is_pioneer ? 'Pioneiro' : 'Não pioneiro',
            'gender' => $this->gender,
            'gender_label' => $this->getGenderLabel(),
            'start_day' => $this->start_day,
            'pairing_preference_mode' => $this->pairing_preference_mode,
            'pairing_preference_mode_label' => $this->getPreferenceModeLabel(),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s')
        ];

        // Incluir relacionamentos se solicitado
        if ($includeRelationships) {
            $data['relationships'] = [
                'fixed_pairs_as_one' => $this->fixedPairsAsOne->count(),
                'fixed_pairs_as_two' => $this->fixedPairsAsTwo->count(),
                'total_fixed_pairs' => $this->fixedPairsAsOne->count() + $this->fixedPairsAsTwo->count(),
                'restrictions_made' => $this->requestedRestrictions->count(),
                'restrictions_received' => $this->restrictedByOthers->count(),
                'preferences_made' => $this->requestedPreferences->count(),
                'preferences_received' => $this->preferredByOthers->count()
            ];
        }

        // Incluir resumo se solicitado
        if ($includeSummary) {
            $canDeactivate = $this->canBeDeactivated();
            $data['summary'] = [
                'can_be_deactivated' => $canDeactivate['can_deactivate'],
                'deactivation_warnings' => [
                    'has_fixed_pairs' => $canDeactivate['has_fixed_pairs'],
                    'has_restrictions' => $canDeactivate['has_restrictions'],
                    'has_preferences' => $canDeactivate['has_preferences']
                ]
            ];
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
                'resource' => 'Publisher'
            ]
        ];
    }
}