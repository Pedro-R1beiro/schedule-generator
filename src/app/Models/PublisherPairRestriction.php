<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PublisherPairRestriction extends Model
{
    use HasFactory;

    protected $table = 'publisher_pair_restrictions';
    
    protected $fillable = [
        'requester_publisher_id',
        'restricted_publisher_id'
    ];

    protected $casts = [
        'requester_publisher_id' => 'integer',
        'restricted_publisher_id' => 'integer'
    ];

    // Relacionamento com o publisher que solicitou a restrição
    public function requesterPublisher()
    {
        return $this->belongsTo(Publisher::class, 'requester_publisher_id');
    }

    // Relacionamento com o publisher restrito
    public function restrictedPublisher()
    {
        return $this->belongsTo(Publisher::class, 'restricted_publisher_id');
    }

    // Scope para buscar restrições de um publisher específico
    public function scopeForRequester($query, $publisherId)
    {
        return $query->where('requester_publisher_id', $publisherId);
    }

    // Scope para buscar restrições contra um publisher específico
    public function scopeAgainstPublisher($query, $publisherId)
    {
        return $query->where('restricted_publisher_id', $publisherId);
    }

    // Verifica se existe restrição entre dois publishers
    public static function hasRestriction($requesterId, $restrictedId)
    {
        return self::where('requester_publisher_id', $requesterId)
                   ->where('restricted_publisher_id', $restrictedId)
                   ->exists();
    }

    // Verifica restrição em ambas as direções
    public static function hasAnyRestriction($publisherId1, $publisherId2)
    {
        return self::where(function ($query) use ($publisherId1, $publisherId2) {
                $query->where('requester_publisher_id', $publisherId1)
                      ->where('restricted_publisher_id', $publisherId2);
            })->orWhere(function ($query) use ($publisherId1, $publisherId2) {
                $query->where('requester_publisher_id', $publisherId2)
                      ->where('restricted_publisher_id', $publisherId1);
            })->exists();
    }

    // Validação para evitar auto-restrição
    public static function rules()
    {
        return [
            'requester_publisher_id' => 'required|exists:publishers,id|different:restricted_publisher_id',
            'restricted_publisher_id' => 'required|exists:publishers,id|different:requester_publisher_id',
        ];
    }

    public static function messages()
    {
        return [
            'requester_publisher_id.required' => 'O publisher solicitante é obrigatório.',
            'requester_publisher_id.exists' => 'O publisher solicitante selecionado não existe.',
            'requester_publisher_id.different' => 'Não é possível criar restrição contra o mesmo publisher.',
            'restricted_publisher_id.required' => 'O publisher restrito é obrigatório.',
            'restricted_publisher_id.exists' => 'O publisher restrito selecionado não existe.',
            'restricted_publisher_id.different' => 'Não é possível criar restrição contra o mesmo publisher.',
        ];
    }
}
