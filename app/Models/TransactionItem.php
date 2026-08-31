<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'product_id',
        'product_unit_id',
        'product_name',
        'unit_name',
        'conversion_ratio',
        'cost_price',
        'selling_price',
        'quantity',
        'base_quantity',
        'discount_amount',
        'subtotal',
        'profit',
    ];

    protected $casts = [
        'conversion_ratio' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'quantity' => 'decimal:2',
        'base_quantity' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'profit' => 'decimal:2',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class, 'product_unit_id');
    }
}
