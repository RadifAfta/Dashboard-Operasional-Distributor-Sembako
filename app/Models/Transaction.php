<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_number',
        'customer_id',
        'user_id',
        'subtotal',
        'discount_amount',
        'total_amount',
        'cost_total',
        'profit_total',
        'payment_method',
        'payment_status',
        'cash_given',
        'cash_change',
        'bank_name',
        'reference_number',
        'due_date',
        'credit_days',
        'paid_amount',
        'remaining_debt',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'cost_total' => 'decimal:2',
        'profit_total' => 'decimal:2',
        'cash_given' => 'decimal:2',
        'cash_change' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_debt' => 'decimal:2',
        'due_date' => 'date:Y-m-d',
    ];

    protected $appends = [
        'is_overdue',
        'days_overdue',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function debtPayments(): HasMany
    {
        return $this->hasMany(DebtPayment::class);
    }

    public function getIsOverdueAttribute(): bool
    {
        if ($this->payment_status === 'paid' || ! $this->due_date) {
            return false;
        }

        return Carbon::parse($this->due_date)->endOfDay()->isPast();
    }

    public function getDaysOverdueAttribute(): int
    {
        if (! $this->is_overdue || ! $this->due_date) {
            return 0;
        }

        return (int) Carbon::parse($this->due_date)->startOfDay()->diffInDays(now()->startOfDay());
    }
}
