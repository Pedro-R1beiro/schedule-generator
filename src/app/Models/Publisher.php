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

    // Preferências solicitadas por este publisher
    public function requestedPreferences()
    {
        return $this->hasMany(PublisherPairPreference::class, 'requester_publisher_id');
    }

    // Preferências onde este publisher é preferido
    public function preferredByOthers()
    {
        return $this->hasMany(PublisherPairPreference::class, 'preferred_publisher_id');
    }

    // Todas as preferências envolvendo este publisher
    public function allPreferences()
    {
        return $this->requestedPreferences->merge($this->preferredByOthers);
    }

    // Retorna lista de publishers preferidos por este
    public function preferredPublishers()
    {
        return Publisher::whereIn('id', function ($query) {
            $query->select('preferred_publisher_id')
                  ->from('publisher_pair_preferences')
                  ->where('requester_publisher_id', $this->id);
        })->get();
    }

    // Retorna lista de publishers que preferem este
    public function publishersThatPreferMe()
    {
        return Publisher::whereIn('id', function ($query) {
            $query->select('requester_publisher_id')
                  ->from('publisher_pair_preferences')
                  ->where('preferred_publisher_id', $this->id);
        })->get();
    }

    // Verifica se este publisher tem preferência por outro
    public function hasPreferenceFor($publisherId)
    {
        return PublisherPairPreference::hasPreference($this->id, $publisherId);
    }

    // Verifica se este publisher é preferido por outro
    public function isPreferredBy($publisherId)
    {
        return PublisherPairPreference::hasPreference($publisherId, $this->id);
    }

    // Verifica se há preferência em qualquer direção
    public function hasAnyPreferenceWith($publisherId)
    {
        return PublisherPairPreference::hasAnyPreference($this->id, $publisherId);
    }

    // Verifica se o modo é ONLY
    public function isPreferenceModeOnly()
    {
        return $this->pairing_preference_mode === self::PREFERENCE_MODE_ONLY;
    }

    // Verifica se o modo é PRIORITY
    public function isPreferenceModePriority()
    {
        return $this->pairing_preference_mode === self::PREFERENCE_MODE_PRIORITY;
    }

    // Scopes existentes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePioneers($query)
    {
        return $query->where('is_pioneer', true);
    }

    public function scopePreferenceModeOnly($query)
    {
        return $query->where('pairing_preference_mode', self::PREFERENCE_MODE_ONLY);
    }

    public function scopePreferenceModePriority($query)
    {
        return $query->where('pairing_preference_mode', self::PREFERENCE_MODE_PRIORITY);
    }

    // Relacionamentos com FixedPair (adicionados anteriormente)
    public function fixedPairsAsOne()
    {
        return $this->hasMany(FixedPair::class, 'publisher_one_id');
    }

    public function fixedPairsAsTwo()
    {
        return $this->hasMany(FixedPair::class, 'publisher_two_id');
    }

    // Relacionamentos com Restrições (adicionados anteriormente)
    public function requestedRestrictions()
    {
        return $this->hasMany(PublisherPairRestriction::class, 'requester_publisher_id');
    }

    public function restrictedByOthers()
    {
        return $this->hasMany(PublisherPairRestriction::class, 'restricted_publisher_id');
    }
}