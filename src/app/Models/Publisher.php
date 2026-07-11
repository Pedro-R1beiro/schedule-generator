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
        'start_day'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_manual' => 'boolean',
        'is_pioneer' => 'boolean',
        'monthly_limit' => 'integer',
        'weekly_limit' => 'integer',
        'start_day' => 'integer'
    ];

    // Restrições solicitadas por este publisher
    public function requestedRestrictions()
    {
        return $this->hasMany(PublisherPairRestriction::class, 'requester_publisher_id');
    }

    // Restrições onde este publisher é restrito
    public function restrictedByOthers()
    {
        return $this->hasMany(PublisherPairRestriction::class, 'restricted_publisher_id');
    }

    // Todas as restrições envolvendo este publisher
    public function allRestrictions()
    {
        return $this->requestedRestrictions->merge($this->restrictedByOthers);
    }

    // Verifica se este publisher tem restrição contra outro
    public function hasRestrictionAgainst($publisherId)
    {
        return PublisherPairRestriction::hasRestriction($this->id, $publisherId);
    }

    // Verifica se este publisher é restrito por outro
    public function isRestrictedBy($publisherId)
    {
        return PublisherPairRestriction::hasRestriction($publisherId, $this->id);
    }

    // Verifica se há restrição em qualquer direção
    public function hasAnyRestrictionWith($publisherId)
    {
        return PublisherPairRestriction::hasAnyRestriction($this->id, $publisherId);
    }

    // Retorna lista de publishers restritos por este
    public function restrictedPublishers()
    {
        return Publisher::whereIn('id', function ($query) {
            $query->select('restricted_publisher_id')
                  ->from('publisher_pair_restrictions')
                  ->where('requester_publisher_id', $this->id);
        })->get();
    }

    // Retorna lista de publishers que restringem este
    public function publishersThatRestrictMe()
    {
        return Publisher::whereIn('id', function ($query) {
            $query->select('requester_publisher_id')
                  ->from('publisher_pair_restrictions')
                  ->where('restricted_publisher_id', $this->id);
        })->get();
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

    // Relacionamentos com FixedPair (adicionados anteriormente)
    public function fixedPairsAsOne()
    {
        return $this->hasMany(FixedPair::class, 'publisher_one_id');
    }

    public function fixedPairsAsTwo()
    {
        return $this->hasMany(FixedPair::class, 'publisher_two_id');
    }
}