<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TimeSlotRequest extends FormRequest
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
            'start_time' => ['required', 'date_format:H:i:s'],
            'end_time' => ['required', 'date_format:H:i:s', 'after:start_time'],
            'is_active' => ['sometimes', 'boolean'],
        ];

        // Para atualização, tornar campos opcionais
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['start_time'] = ['sometimes', 'required', 'date_format:H:i:s'];
            $rules['end_time'] = ['sometimes', 'required', 'date_format:H:i:s', 'after:start_time'];
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
            'start_time.required' => 'O horário inicial é obrigatório.',
            'start_time.date_format' => 'O horário inicial deve estar no formato HH:MM:SS.',
            'end_time.required' => 'O horário final é obrigatório.',
            'end_time.date_format' => 'O horário final deve estar no formato HH:MM:SS.',
            'end_time.after' => 'O horário final deve ser maior que o horário inicial.',
            'is_active.boolean' => 'O campo is_active deve ser verdadeiro ou falso.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Converter para o formato esperado se necessário
        if ($this->has('start_time') && !str_contains($this->start_time, ':')) {
            $this->merge([
                'start_time' => $this->start_time . ':00'
            ]);
        }

        if ($this->has('end_time') && !str_contains($this->end_time, ':')) {
            $this->merge([
                'end_time' => $this->end_time . ':00'
            ]);
        }
    }
}