<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Transaction extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'uuid',
        'from_wallet_id',
        'to_wallet_id',
        'type',
        'amount',
        'currency',
        'status',
        'description',
        'metadata',
        'reference_id',
        'processed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'processed_at' => 'datetime',
    ];

    protected $hidden = [
        'id',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($transaction) {
            $transaction->uuid = Str::uuid();
        });
    }

    public function fromWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'from_wallet_id');
    }

    public function toWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'to_wallet_id');
    }

    public function reversal(): HasOne
    {
        return $this->hasOne(TransactionReversal::class, 'original_transaction_id');
    }

    public function reversalTransaction(): HasOne
    {
        return $this->hasOne(TransactionReversal::class, 'reversal_transaction_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'amount', 'type'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isReversed(): bool
    {
        return $this->status === 'reversed';
    }

    public function canBeReversed(): bool
    {
        return $this->isCompleted() && !$this->isReversed();
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'processed_at' => now(),
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update([
            'status' => 'failed',
            'processed_at' => now(),
        ]);
    }

    public function markAsReversed(): void
    {
        $this->update([
            'status' => 'reversed',
            'processed_at' => now(),
        ]);
    }
}
