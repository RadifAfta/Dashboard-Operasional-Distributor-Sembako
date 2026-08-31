<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'barcode',
        'name',
        'category',
        'cost_price',
        'selling_price',
        'base_unit',
        'min_stock',
        'current_stock',
        'status',
        'description',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'selling_price' => 'decimal:2',
        'min_stock' => 'decimal:2',
        'current_stock' => 'decimal:2',
    ];

    protected $appends = [
        'is_critical_stock',
    ];

    public function units(): HasMany
    {
        return $this->hasMany(ProductUnit::class);
    }

    public function transactionItems(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function getIsCriticalStockAttribute(): bool
    {
        return $this->current_stock <= $this->min_stock;
    }
}
