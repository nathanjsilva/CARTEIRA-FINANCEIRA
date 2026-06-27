<?php

namespace App\Presentation\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'        => $this->uuid,
            'balance'   => (float) $this->balance,
            'currency'  => $this->currency,
            'is_active' => $this->is_active,
        ];
    }
}
