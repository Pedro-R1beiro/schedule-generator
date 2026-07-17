<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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
    public function scopeForRequester(Builder $query, int $publisherId): Builder
    {
        return $query->where('requester_publisher_id', $publisherId);
    }

    // Scope para buscar restrições contra um publisher específico
    public function scopeAgainstPublisher(Builder $query, int $publisherId): Builder
    {
        return $query->where('restricted_publisher_id', $publisherId);
    }

    // Verifica se existe restrição entre dois publishers
    public static function hasRestriction(int $requesterId, int $restrictedId): bool
    {
        return self::where('requester_publisher_id', $requesterId)
                   ->where('restricted_publisher_id', $restrictedId)
                   ->exists();
    }

    // Verifica restrição em ambas as direções
    public static function hasAnyRestriction(int $publisherId1, int $publisherId2): bool
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
    public static function rules(): array
    {
        return [
            'requester_publisher_id' => 'required|exists:publishers,id|different:restricted_publisher_id',
            'restricted_publisher_id' => 'required|exists:publishers,id|different:requester_publisher_id',
        ];
    }

    public static function messages(): array
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

    // Boot para validações automáticas
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            // Validar se os publishers são diferentes
            if ($model->requester_publisher_id === $model->restricted_publisher_id) {
                throw new \Exception('Não é possível criar restrição contra o mesmo publisher.');
            }

            // Validar se a restrição já existe
            $exists = self::where('requester_publisher_id', $model->requester_publisher_id)
                          ->where('restricted_publisher_id', $model->restricted_publisher_id)
                          ->exists();
            
            if ($exists) {
                throw new \Exception('Esta restrição já existe.');
            }

            // Validar se os publishers existem
            $requester = Publisher::find($model->requester_publisher_id);
            $restricted = Publisher::find($model->restricted_publisher_id);

            if (!$requester || !$restricted) {
                throw new \Exception('Um ou ambos os publishers não existem.');
            }

            // Validar se ambos estão ativos
            if (!$requester->is_active || !$restricted->is_active) {
                throw new \Exception('Ambos os publishers devem estar ativos.');
            }
        });
    }
}
