<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublisherPairPreferenceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Ajuste conforme sua lógica de autenticação
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'requester_publisher_id' => [
                'required',
                'integer',
                'exists:publisher,id',
                'different:preferred_publisher_id'
            ],
            'preferred_publisher_id' => [
                'required',
                'integer',
                'exists:publisher,id',
                'different:requester_publisher_id',
                // Validação customizada para verificar se a preferência já existe
                function ($attribute, $value, $fail) {
                    $requesterId = $this->input('requester_publisher_id');
                    if ($requesterId && $value) {
                        $exists = \App\Models\PublisherPairPreference::where('requester_publisher_id', $requesterId)
                                                                      ->where('preferred_publisher_id', $value)
                                                                      ->exists();
                        if ($exists) {
                            $fail('Esta preferência já existe.');
                        }
                    }
                }
            ]
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'requester_publisher_id.required' => 'O publisher solicitante é obrigatório.',
            'requester_publisher_id.integer' => 'O publisher solicitante deve ser um número inteiro.',
            'requester_publisher_id.exists' => 'O publisher solicitante selecionado não existe.',
            'requester_publisher_id.different' => 'Não é possível criar preferência para o mesmo publisher.',
            
            'preferred_publisher_id.required' => 'O publisher preferido é obrigatório.',
            'preferred_publisher_id.integer' => 'O publisher preferido deve ser um número inteiro.',
            'preferred_publisher_id.exists' => 'O publisher preferido selecionado não existe.',
            'preferred_publisher_id.different' => 'Não é possível criar preferência para o mesmo publisher.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Garantir que os IDs sejam inteiros
        if ($this->has('requester_publisher_id')) {
            $this->merge([
                'requester_publisher_id' => (int) $this->requester_publisher_id
            ]);
        }

        if ($this->has('preferred_publisher_id')) {
            $this->merge([
                'preferred_publisher_id' => (int) $this->preferred_publisher_id
            ]);
        }
    }
}