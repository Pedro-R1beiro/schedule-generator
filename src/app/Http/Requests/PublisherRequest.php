<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublisherRequest extends FormRequest
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
        $id = $this->route('id');

        $rules = [
            'name' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:150',
                Rule::unique('publisher', 'name')->ignore($id)
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20'
            ],
            'is_active' => [
                'sometimes',
                'boolean'
            ],
            'is_manual' => [
                'sometimes',
                'boolean'
            ],
            'monthly_limit' => [
                'sometimes',
                'integer',
                'min:0'
            ],
            'weekly_limit' => [
                'sometimes',
                'integer',
                'min:0'
            ],
            'is_pioneer' => [
                'sometimes',
                'boolean'
            ],
            'gender' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'in:M,F'
            ],
            'start_day' => [
                $isUpdate ? 'sometimes' : 'required',
                'integer',
                'min:1',
                'max:31'
            ],
            'pairing_preference_mode' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'in:ONLY,PRIORITY'
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
            'name.required' => 'O nome do publisher é obrigatório.',
            'name.string' => 'O nome deve ser um texto.',
            'name.max' => 'O nome não pode ter mais que 150 caracteres.',
            'name.unique' => 'Já existe um publisher com este nome.',
            
            'phone.string' => 'O telefone deve ser um texto.',
            'phone.max' => 'O telefone não pode ter mais que 20 caracteres.',
            
            'is_active.boolean' => 'O campo is_active deve ser verdadeiro ou falso.',
            'is_manual.boolean' => 'O campo is_manual deve ser verdadeiro ou falso.',
            
            'monthly_limit.integer' => 'O limite mensal deve ser um número inteiro.',
            'monthly_limit.min' => 'O limite mensal não pode ser negativo.',
            
            'weekly_limit.integer' => 'O limite semanal deve ser um número inteiro.',
            'weekly_limit.min' => 'O limite semanal não pode ser negativo.',
            
            'is_pioneer.boolean' => 'O campo is_pioneer deve ser verdadeiro ou falso.',
            
            'gender.required' => 'O gênero é obrigatório.',
            'gender.in' => 'O gênero deve ser M ou F.',
            
            'start_day.required' => 'O dia de início é obrigatório.',
            'start_day.integer' => 'O dia de início deve ser um número inteiro.',
            'start_day.min' => 'O dia de início deve ser pelo menos 1.',
            'start_day.max' => 'O dia de início não pode ser maior que 31.',
            
            'pairing_preference_mode.required' => 'O modo de preferência é obrigatório.',
            'pairing_preference_mode.in' => 'O modo de preferência deve ser ONLY ou PRIORITY.'
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Converter campos booleanos
        $booleanFields = ['is_active', 'is_manual', 'is_pioneer'];
        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $this->merge([
                    $field => filter_var($this->$field, FILTER_VALIDATE_BOOLEAN)
                ]);
            }
        }

        // Garantir que campos numéricos sejam inteiros
        $integerFields = ['monthly_limit', 'weekly_limit', 'start_day'];
        foreach ($integerFields as $field) {
            if ($this->has($field) && $this->$field !== null) {
                $this->merge([
                    $field => (int) $this->$field
                ]);
            }
        }
    }
}