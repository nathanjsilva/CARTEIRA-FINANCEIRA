<?php

namespace App\Presentation\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'           => $this->uuid,
            'type'         => $this->type,
            'amount'       => (float) $this->amount,
            'currency'     => $this->currency,
            'status'       => $this->status,
            'description'  => $this->description,
            'created_at'   => $this->created_at?->toISOString(),
            'processed_at' => $this->processed_at?->toISOString(),
        ];
    }
}
