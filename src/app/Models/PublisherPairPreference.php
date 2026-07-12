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

    // Scope para buscar preferências de um publisher específico
    public function scopeForRequester($query, $publisherId)
    {
        return $query->where('requester_publisher_id', $publisherId);
    }

    // Scope para buscar preferências contra um publisher específico
    public function scopeForPreferred($query, $publisherId)
    {
        return $query->where('preferred_publisher_id', $publisherId);
    }

    // Verifica se existe preferência entre dois publishers
    public static function hasPreference($requesterId, $preferredId)
    {
        return self::where('requester_publisher_id', $requesterId)
                   ->where('preferred_publisher_id', $preferredId)
                   ->exists();
    }

    // Verifica preferência em ambas as direções
    public static function hasAnyPreference($publisherId1, $publisherId2)
    {
        return self::where(function ($query) use ($publisherId1, $publisherId2) {
                $query->where('requester_publisher_id', $publisherId1)
                      ->where('preferred_publisher_id', $publisherId2);
            })->orWhere(function ($query) use ($publisherId1, $publisherId2) {
                $query->where('requester_publisher_id', $publisherId2)
                      ->where('preferred_publisher_id', $publisherId1);
            })->exists();
    }

    // Validação para evitar auto-preferência
    public static function rules()
    {
        return [
            'requester_publisher_id' => 'required|exists:publishers,id|different:preferred_publisher_id',
            'preferred_publisher_id' => 'required|exists:publishers,id|different:requester_publisher_id',
        ];
    }

    public static function messages()
    {
        return [
            'requester_publisher_id.required' => 'O publisher solicitante é obrigatório.',
            'requester_publisher_id.exists' => 'O publisher solicitante selecionado não existe.',
            'requester_publisher_id.different' => 'Não é possível criar preferência para o mesmo publisher.',
            'preferred_publisher_id.required' => 'O publisher preferido é obrigatório.',
            'preferred_publisher_id.exists' => 'O publisher preferido selecionado não existe.',
            'preferred_publisher_id.different' => 'Não é possível criar preferência para o mesmo publisher.',
        ];
    }

    // Boot para validações automáticas
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Validar se os publishers são diferentes
            if ($model->requester_publisher_id === $model->preferred_publisher_id) {
                throw new \Exception('Não é possível criar preferência para o mesmo publisher.');
            }

            // Validar se a preferência já existe
            $exists = self::where('requester_publisher_id', $model->requester_publisher_id)
                          ->where('preferred_publisher_id', $model->preferred_publisher_id)
                          ->exists();
            
            if ($exists) {
                throw new \Exception('Esta preferência já existe.');
            }

            // Validar se os publishers existem
            $requester = Publisher::find($model->requester_publisher_id);
            $preferred = Publisher::find($model->preferred_publisher_id);

            if (!$requester || !$preferred) {
                throw new \Exception('Um ou ambos os publishers não existem.');
            }

            // Validar se ambos estão ativos
            if (!$requester->is_active || !$preferred->is_active) {
                throw new \Exception('Ambos os publishers devem estar ativos.');
            }
        });
    }
}