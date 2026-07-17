<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublisherPairPreference extends Model
{
    use HasFactory;

    protected $table = 'publisher_pair_preferences';
    
    protected $fillable = [
        'requester_publisher_id',
        'preferred_publisher_id'
    ];

    protected $casts = [
        'requester_publisher_id' => 'integer',
        'preferred_publisher_id' => 'integer'
    ];

    // Relacionamento com o publisher que solicitou a preferência
    public function requesterPublisher()
    {
        return $this->belongsTo(Publisher::class, 'requester_publisher_id');
    }

    // Relacionamento com o publisher preferido
    public function preferredPublisher()
    {
        return $this->belongsTo(Publisher::class, 'preferred_publisher_id');
    }

    // Verifica se o requester está no modo ONLY
    public function requesterIsOnlyMode(): bool
    {
        return $this->requesterPublisher->pairing_preference_mode === 'ONLY';
    }

    // Verifica se o requester está no modo PRIORITY
    public function requesterIsPriorityMode(): bool
    {
        return $this->requesterPublisher->pairing_preference_mode === 'PRIORITY';
    }

    // Retorna o modo do requester em português
    public function getRequesterModeLabel(): string
    {
        $modes = [
            'ONLY' => 'Somente',
            'PRIORITY' => 'Prioridade'
        ];
        return $modes[$this->requesterPublisher->pairing_preference_mode] ?? 'Desconhecido';
    }

    // Scope para buscar por requester
    public function scopeForRequester($query, int $publisherId)
    {
        return $query->where('requester_publisher_id', $publisherId);
    }

    // Scope para buscar por preferred
    public function scopeForPreferred($query, int $publisherId)
    {
        return $query->where('preferred_publisher_id', $publisherId);
    }

    // Scope para buscar por par
    public function scopeForPair($query, int $publisherOneId, int $publisherTwoId)
    {
        return $query->where(function ($q) use ($publisherOneId, $publisherTwoId) {
            $q->where('requester_publisher_id', $publisherOneId)
              ->where('preferred_publisher_id', $publisherTwoId);
        })->orWhere(function ($q) use ($publisherOneId, $publisherTwoId) {
            $q->where('requester_publisher_id', $publisherTwoId)
              ->where('preferred_publisher_id', $publisherOneId);
        });
    }

    // Verifica se há preferência específica
    public static function hasPreference(int $requesterId, int $preferredId): bool
    {
        return self::where('requester_publisher_id', $requesterId)
                   ->where('preferred_publisher_id', $preferredId)
                   ->exists();
    }

    // Verifica preferência em ambas as direções
    public static function hasAnyPreference(int $publisherId1, int $publisherId2): bool
    {
        return self::where(function ($query) use ($publisherId1, $publisherId2) {
                $query->where('requester_publisher_id', $publisherId1)
                      ->where('preferred_publisher_id', $publisherId2);
            })->orWhere(function ($query) use ($publisherId1, $publisherId2) {
                $query->where('requester_publisher_id', $publisherId2)
                      ->where('preferred_publisher_id', $publisherId1);
            })->exists();
    }

    // Retorna nome completo para exibição
    public function getFullNameAttribute(): string
    {
        return $this->requesterPublisher->name . ' prefere ' . $this->preferredPublisher->name;
    }

    // Retorna nome completo com modo
    public function getFullNameWithModeAttribute(): string
    {
        $mode = $this->getRequesterModeLabel();
        return $this->requesterPublisher->name . " ({$mode}) prefere " . $this->preferredPublisher->name;
    }
}