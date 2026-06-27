<?php

namespace App\Application\Services;

use App\Application\DTOs\DepositRequestDTO;
use App\Application\DTOs\TransactionResponseDTO;
use App\Domain\Entities\Transaction;
use App\Domain\ValueObjects\Money;
use App\Domain\Repositories\UserRepository;
use App\Domain\Repositories\TransactionRepository;
use App\Domain\Exceptions\UserNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class DepositService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly TransactionRepository $transactionRepository,
    ) {}

    public function execute(DepositRequestDTO $request): TransactionResponseDTO
    {
        $user = $this->userRepository->findById($request->userId);
        if (!$user) {
            throw new UserNotFoundException($request->userId);
        }

        $amount = Money::of($request->amount);

        $transaction = DB::transaction(function () use ($user, $amount) {
            $user->deposit($amount);

            $this->userRepository->save($user);

            $transaction = Transaction::deposit(
                id: (string) Str::uuid(),
                userId: $user->getId(),
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
