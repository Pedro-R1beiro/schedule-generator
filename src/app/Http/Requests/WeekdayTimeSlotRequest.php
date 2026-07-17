<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WeekdayTimeSlotRequest extends FormRequest
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
        $rules = [
            'weekday_id' => [
                'required',
                'integer',
                'exists:weekdays,id'
            ],
            'time_slot_id' => [
                'required',
                'integer',
                'exists:time_slots,id',
                // Validação customizada para verificar se o par já existe
                Rule::unique('weekday_time_slots')
                    ->where('weekday_id', $this->input('weekday_id'))
                    ->where('time_slot_id', $this->input('time_slot_id'))
            ]
        ];

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
            'weekday_id.required' => 'O dia da semana é obrigatório.',
            'weekday_id.integer' => 'O dia da semana deve ser um número inteiro.',
            'weekday_id.exists' => 'O dia da semana selecionado não existe.',
            
            'time_slot_id.required' => 'O horário é obrigatório.',
            'time_slot_id.integer' => 'O horário deve ser um número inteiro.',
            'time_slot_id.exists' => 'O horário selecionado não existe.',
            'time_slot_id.unique' => 'Este horário já está associado a este dia da semana.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Garantir que os IDs sejam inteiros
        if ($this->has('weekday_id')) {
            $this->merge([
                'weekday_id' => (int) $this->weekday_id
            ]);
        }

        if ($this->has('time_slot_id')) {
            $this->merge([
                'time_slot_id' => (int) $this->time_slot_id
            ]);
        }
    }
}