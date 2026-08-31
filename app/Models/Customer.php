<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'phone',
        'address',
        'credit_limit',
        'current_debt',
        'status',
    ];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'current_debt' => 'decimal:2',
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function debtPayments(): HasMany
    {
        return $this->hasMany(DebtPayment::class);
    }
}
