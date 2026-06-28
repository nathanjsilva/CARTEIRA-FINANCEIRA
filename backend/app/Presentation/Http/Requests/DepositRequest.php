<?php

namespace App\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DepositRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'amount'      => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required' => 'O valor é obrigatório.',
            'amount.numeric'  => 'O valor deve ser um número.',
            'amount.min'      => 'O valor mínimo para depósito é R$ 0,01.',
            'description.max' => 'A descrição deve ter no máximo 255 caracteres.',
        ];
    }
}
