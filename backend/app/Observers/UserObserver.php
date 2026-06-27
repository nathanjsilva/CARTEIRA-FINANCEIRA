<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    /**
     * Manipula o evento "creating" do User.
     */
    public function creating(User $user): void
    {
        Log::info('Usuário sendo criado', [
            'email' => $user->email,
            'name' => $user->name
        ]);
    }

    /**
     * Manipula o evento "created" do User.
     */
    public function created(User $user): void
    {
        Log::info('Usuário criado com sucesso', [
            'user_id' => $user->id,
            'email' => $user->email
        ]);

        // Criar carteira padrão para novo usuário
        $user->createDefaultWallet();
        
        Log::info('Carteira padrão criada para usuário', [
            'user_id' => $user->id,
            'wallet_id' => $user->getDefaultWallet()->id ?? null
        ]);
    }

    /**
     * Manipula o evento "updating" do User.
     */
    public function updating(User $user): void
    {
        Log::info('Usuário sendo atualizado', [
            'user_id' => $user->id,
            'changes' => $user->getDirty()
        ]);
    }

    /**
     * Manipula o evento "updated" do User.
     */
    public function updated(User $user): void
    {
        Log::info('Usuário atualizado com sucesso', [
            'user_id' => $user->id,
            'updated_fields' => array_keys($user->getDirty())
        ]);
    }

    /**
     * Manipula o evento "deleting" do User.
     */
    public function deleting(User $user): void
    {
        Log::warning('Usuário sendo excluído', [
            'user_id' => $user->id,
            'email' => $user->email
        ]);
    }

    /**
     * Manipula o evento "deleted" do User.
     */
    public function deleted(User $user): void
    {
        Log::warning('Usuário excluído com sucesso', [
            'user_id' => $user->id
        ]);
    }

    /**
     * Manipula o evento "restored" do User.
     */
    public function restored(User $user): void
    {
        Log::info('Usuário restaurado', [
            'user_id' => $user->id
        ]);
    }

    /**
     * Manipula o evento "force deleted" do User.
     */
    public function forceDeleted(User $user): void
    {
        Log::warning('Usuário excluído permanentemente', [
            'user_id' => $user->id
        ]);
    }
}
