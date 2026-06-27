<?php

namespace App\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'recipient_id' => ['required', 'exists:users,id'],
            'amount'       => ['required', 'numeric', 'min:0.01'],
            'description'  => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'recipient_id.exists' => 'Destinatário não encontrado.',
            'amount.min'          => 'O valor mínimo para transferência é R$ 0,01.',
        ];
    }
}
