<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductUnit extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'unit_name',
        'conversion_ratio',
        'selling_price',
        'barcode',
    ];

    protected $casts = [
        'conversion_ratio' => 'decimal:2',
        'selling_price' => 'decimal:2',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
