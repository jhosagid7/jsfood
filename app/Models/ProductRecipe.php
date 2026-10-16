<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductRecipe extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'quantity_needed' => 'float',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function supply(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'supply_id');
    }
}
