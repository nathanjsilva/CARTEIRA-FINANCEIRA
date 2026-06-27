<?php

namespace App\Application\DTOs;

final class TransactionResponseDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $type,
        public readonly float $amount,
        public readonly string $status,
        public readonly \DateTime $createdAt,
        public readonly ?string $senderId = null,
        public readonly ?string $recipientId = null,
    ) {}

    public function toArray(): array
    {
        return [
            'id'           => $this->id,
            'type'         => $this->type,
            'amount'       => $this->amount,
            'status'       => $this->status,
            'sender_id'    => $this->senderId,
            'recipient_id' => $this->recipientId,
            'created_at'   => $this->createdAt->format('Y-m-d H:i:s'),
        ];
    }
}
