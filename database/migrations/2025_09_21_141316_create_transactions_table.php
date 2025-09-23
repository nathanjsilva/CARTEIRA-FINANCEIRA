<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('from_wallet_id')->nullable()->constrained('wallets')->onDelete('cascade');
            $table->foreignId('to_wallet_id')->nullable()->constrained('wallets')->onDelete('cascade');
            $table->enum('type', ['deposit', 'withdrawal', 'transfer', 'reversal']);
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('BRL');
            $table->enum('status', ['pending', 'completed', 'failed', 'reversed'])->default('pending');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->string('reference_id')->nullable()->unique();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            $table->index(['from_wallet_id', 'status']);
            $table->index(['to_wallet_id', 'status']);
            $table->index(['type', 'status']);
            $table->index(['processed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
