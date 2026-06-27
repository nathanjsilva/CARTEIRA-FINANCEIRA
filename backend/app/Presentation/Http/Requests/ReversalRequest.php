<?php

namespace App\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReversalRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'transaction_id' => ['required', 'exists:transactions,uuid'],
            'reason'         => ['required', 'in:user_request,system_error,fraud_detection,compliance'],
            'description'    => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'transaction_id.exists' => 'Transação não encontrada.',
            'reason.in'             => 'Motivo inválido. Use: user_request, system_error, fraud_detection ou compliance.',
        ];
    }
}
