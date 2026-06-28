<?php

namespace App\Presentation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TransferRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'recipient_id' => ['required', 'exists:users,id', Rule::notIn([$this->user()->id])],
            'amount'       => ['required', 'numeric', 'min:0.01'],
            'description'  => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'recipient_id.exists'   => 'Destinatário não encontrado.',
            'recipient_id.not_in'   => 'Você não pode transferir para a sua própria conta.',
            'amount.min'          => 'O valor mínimo para transferência é R$ 0,01.',
        ];
    }
}
