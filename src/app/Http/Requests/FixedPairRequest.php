<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FixedPairRequest extends FormRequest
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
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        $rules = [
            'weekday_time_slot_id' => [
                $isUpdate ? 'sometimes' : 'required',
                'integer',
                'exists:weekday_time_slots,id'
            ],
            'publisher_one_id' => [
                $isUpdate ? 'sometimes' : 'required',
                'integer',
                'exists:publisher,id',
                'different:publisher_two_id'
            ],
            'publisher_two_id' => [
                $isUpdate ? 'sometimes' : 'required',
                'integer',
                'exists:publisher,id',
                'different:publisher_one_id'
            ]
        ];

        // Regra customizada para update: não permitir alterar weekday_time_slot_id
        if ($isUpdate && $this->has('weekday_time_slot_id')) {
            $rules['weekday_time_slot_id'][] = function ($attribute, $value, $fail) {
                $fixedPair = \App\Models\FixedPair::find($this->route('id'));
                if ($fixedPair && $fixedPair->weekday_time_slot_id != $value) {
                    $fail('Não é permitido alterar o dia/horário de um par fixo.');
                }
            };
        }

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'weekday_time_slot_id.required' => 'O dia/horário é obrigatório.',
            'weekday_time_slot_id.integer' => 'O dia/horário deve ser um número inteiro.',
            'weekday_time_slot_id.exists' => 'O dia/horário selecionado não existe.',
            
            'publisher_one_id.required' => 'O primeiro publisher é obrigatório.',
            'publisher_one_id.integer' => 'O primeiro publisher deve ser um número inteiro.',
            'publisher_one_id.exists' => 'O primeiro publisher selecionado não existe.',
            'publisher_one_id.different' => 'Os publishers devem ser diferentes.',
            
            'publisher_two_id.required' => 'O segundo publisher é obrigatório.',
            'publisher_two_id.integer' => 'O segundo publisher deve ser um número inteiro.',
            'publisher_two_id.exists' => 'O segundo publisher selecionado não existe.',
            'publisher_two_id.different' => 'Os publishers devem ser diferentes.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Garantir que os IDs sejam inteiros
        $fields = ['weekday_time_slot_id', 'publisher_one_id', 'publisher_two_id'];
        foreach ($fields as $field) {
            if ($this->has($field)) {
                $this->merge([
                    $field => (int) $this->$field
                ]);
            }
        }
    }
}