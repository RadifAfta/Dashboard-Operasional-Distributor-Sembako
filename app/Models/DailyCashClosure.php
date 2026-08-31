<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DailyCashClosure extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'closure_date',
        'total_omzet',
        'total_profit',
        'total_cash_sales',
        'total_credit_sales',
        'expected_cash',
        'actual_cash',
        'difference',
        'denominations',
        'notes',
        'wa_phone',
        'wa_sent_at',
    ];

    protected $casts = [
        'closure_date' => 'date:Y-m-d',
        'total_omzet' => 'decimal:2',
        'total_profit' => 'decimal:2',
        'total_cash_sales' => 'decimal:2',
        'total_credit_sales' => 'decimal:2',
        'expected_cash' => 'decimal:2',
        'actual_cash' => 'decimal:2',
        'difference' => 'decimal:2',
        'denominations' => 'array',
        'wa_sent_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
