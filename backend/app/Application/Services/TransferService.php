<?php

namespace App\Application\Services;

use App\Application\DTOs\TransferRequestDTO;
use App\Application\DTOs\TransactionResponseDTO;
use App\Domain\Entities\Transaction;
use App\Domain\ValueObjects\Money;
use App\Domain\Repositories\UserRepository;
use App\Domain\Repositories\TransactionRepository;
use App\Domain\Exceptions\InsufficientFundsException;
use App\Domain\Exceptions\UserNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class TransferService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TransactionRepository $transactionRepository,
    ) {}

    public function execute(TransferRequestDTO $request): TransactionResponseDTO
    {
        $sender = $this->userRepository->findById($request->senderId);
        if (!$sender) {
            throw new UserNotFoundException($request->senderId);
        }

        $recipient = $this->userRepository->findById($request->recipientId);
        if (!$recipient) {
            throw new UserNotFoundException($request->recipientId);
        }

        $amount = Money::of($request->amount);

        if (!$sender->canTransfer($amount)) {
            throw new InsufficientFundsException($amount, $sender->getBalance());
        }

        $transaction = DB::transaction(function () use ($sender, $recipient, $amount) {
            $sender->transfer($amount, $recipient);

            $this->userRepository->save($sender);
            $this->userRepository->save($recipient);

            $transaction = Transaction::transfer(
                id: (string) Str::uuid(),
                senderId: $sender->getId(),
                recipientId: $recipient->getId(),
                amount: $amount
            );
            $transaction->complete();

            $this->transactionRepository->save($transaction);

            return $transaction;
        });

        return new TransactionResponseDTO(
            id: $transaction->getId(),
            type: $transaction->getType(),
            amount: $transaction->getAmount()->getAmount(),
            status: $transaction->getStatus(),
            createdAt: $transaction->getCreatedAt(),
            senderId: $transaction->getSenderId(),
            recipientId: $transaction->getRecipientId(),
        );
    }
}
