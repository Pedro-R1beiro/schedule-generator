<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Publisher extends Model
{
    use HasFactory;

    protected $table = 'publishers';
    
    protected $fillable = [
        'name',
        'phone',
        'is_active',
        'is_manual',
        'monthly_limit',
        'weekly_limit',
        'is_pioneer',
        'gender',
        'start_day',
        'pairing_preference_mode'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_manual' => 'boolean',
        'is_pioneer' => 'boolean',
        'monthly_limit' => 'integer',
        'weekly_limit' => 'integer',
        'start_day' => 'integer'
    ];

    // Constantes para os modos de preferência
    const PREFERENCE_MODE_ONLY = 'ONLY';
    const PREFERENCE_MODE_PRIORITY = 'PRIORITY';

    // Constantes para gênero
    const GENDER_MALE = 'M';
    const GENDER_FEMALE = 'F';

    // Labels para exibição
    const GENDER_LABELS = [
        'M' => 'Masculino',
        'F' => 'Feminino'
    ];

    const PREFERENCE_MODE_LABELS = [
        'ONLY' => 'Somente',
        'PRIORITY' => 'Prioridade'
    ];

    // Relacionamentos
    public function fixedPairsAsOne()
    {
        return $this->hasMany(FixedPair::class, 'publisher_one_id');
    }

    public function fixedPairsAsTwo()
    {
        return $this->hasMany(FixedPair::class, 'publisher_two_id');
    }

    public function requestedRestrictions()
    {
        return $this->hasMany(PublisherPairRestriction::class, 'requester_publisher_id');
    }

    public function restrictedByOthers()
    {
        return $this->hasMany(PublisherPairRestriction::class, 'restricted_publisher_id');
    }

    public function requestedPreferences()
    {
        return $this->hasMany(PublisherPairPreference::class, 'requester_publisher_id');
    }

    public function preferredByOthers()
    {
        return $this->hasMany(PublisherPairPreference::class, 'preferred_publisher_id');
    }

    // Todos os fixed pairs onde este publisher está envolvido
    public function getAllFixedPairs()
    {
        return FixedPair::where('publisher_one_id', $this->id)
                        ->orWhere('publisher_two_id', $this->id)
                        ->get();
    }

    // Verifica se o publisher está em modo ONLY
    public function isPreferenceModeOnly(): bool
    {
        return $this->pairing_preference_mode === self::PREFERENCE_MODE_ONLY;
    }

    // Verifica se o publisher está em modo PRIORITY
    public function isPreferenceModePriority(): bool
    {
        return $this->pairing_preference_mode === self::PREFERENCE_MODE_PRIORITY;
    }

    // Retorna label do gênero
    public function getGenderLabel(): string
    {
        return self::GENDER_LABELS[$this->gender] ?? 'Desconhecido';
    }

    // Retorna label do modo de preferência
    public function getPreferenceModeLabel(): string
    {
        return self::PREFERENCE_MODE_LABELS[$this->pairing_preference_mode] ?? 'Desconhecido';
    }

    // Retorna label de ativo
    public function getActiveLabel(): string
    {
        return $this->is_active ? 'Ativo' : 'Inativo';
    }

    // Retorna contagem de relacionamentos
    public function getRelationshipsCount(): array
    {
        return [
            'fixed_pairs_as_one' => $this->fixedPairsAsOne()->count(),
            'fixed_pairs_as_two' => $this->fixedPairsAsTwo()->count(),
            'total_fixed_pairs' => $this->fixedPairsAsOne()->count() + $this->fixedPairsAsTwo()->count(),
            'restrictions_made' => $this->requestedRestrictions()->count(),
            'restrictions_received' => $this->restrictedByOthers()->count(),
            'preferences_made' => $this->requestedPreferences()->count(),
            'preferences_received' => $this->preferredByOthers()->count()
        ];
    }

    // Verifica se o publisher pode ser desativado
    public function canBeDeactivated(): array
    {
        $hasFixedPairs = $this->fixedPairsAsOne()->exists() || $this->fixedPairsAsTwo()->exists();
        $hasRestrictions = $this->requestedRestrictions()->exists() || $this->restrictedByOthers()->exists();
        $hasPreferences = $this->requestedPreferences()->exists() || $this->preferredByOthers()->exists();

        return [
            'can_deactivate' => !$hasFixedPairs && !$hasRestrictions && !$hasPreferences,
            'has_fixed_pairs' => $hasFixedPairs,
            'has_restrictions' => $hasRestrictions,
            'has_preferences' => $hasPreferences,
            'fixed_pairs_count' => $this->fixedPairsAsOne()->count() + $this->fixedPairsAsTwo()->count(),
            'restrictions_count' => $this->requestedRestrictions()->count() + $this->restrictedByOthers()->count(),
            'preferences_count' => $this->requestedPreferences()->count() + $this->preferredByOthers()->count()
        ];
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopePioneers($query)
    {
        return $query->where('is_pioneer', true);
    }

    public function scopeByGender($query, string $gender)
    {
        return $query->where('gender', $gender);
    }

    public function scopePreferenceModeOnly($query)
    {
        return $query->where('pairing_preference_mode', self::PREFERENCE_MODE_ONLY);
    }

    public function scopePreferenceModePriority($query)
    {
        return $query->where('pairing_preference_mode', self::PREFERENCE_MODE_PRIORITY);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('name');
    }
}