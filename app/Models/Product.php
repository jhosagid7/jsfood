<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'has_recipe' => 'boolean',
        'track_stock' => 'boolean',
        'send_to_kitchen' => 'boolean',
        'is_active' => 'boolean',
        'price_usd' => 'float',
        'current_stock' => 'float',
        'min_stock' => 'float',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Insumos que componen la receta de este plato.
     */
    public function recipeItems(): HasMany
    {
        return $this->hasMany(ProductRecipe::class, 'product_id');
    }

    /**
     * Si este ítem es un insumo crudo, en qué platos se utiliza.
     */
    public function usedInRecipes(): HasMany
    {
        return $this->hasMany(ProductRecipe::class, 'supply_id');
    }

    /**
     * Descontar stock al vender el producto (Manejo Híbrido: Directo vs Receta).
     */
    public function deductStockForSale(float $quantity, ?int $userId = null, ?string $orderReference = null): void
    {
        if ($this->has_recipe) {
            // Descontar cada insumo según la receta técnica
            foreach ($this->recipeItems()->with('supply')->get() as $recipeItem) {
                $supply = $recipeItem->supply;
                if ($supply && $supply->track_stock) {
                    $deductAmount = $recipeItem->quantity_needed * $quantity;
                    $prevStock = $supply->current_stock;
                    $newStock = $prevStock - $deductAmount;

                    $supply->update(['current_stock' => $newStock]);

                    StockMovement::create([
                        'product_id' => $supply->id,
                        'type' => 'dish_sale',
                        'quantity' => -$deductAmount,
                        'previous_stock' => $prevStock,
                        'new_stock' => $newStock,
                        'user_id' => $userId,
                        'reference' => $orderReference,
                        'notes' => "Consumo por venta de {$quantity} x {$this->name}",
                    ]);
                }
            }
        } elseif ($this->track_stock) {
            // Descuento directo 1 a 1 (refrescos, empaquetados)
            $prevStock = $this->current_stock;
            $newStock = $prevStock - $quantity;

            $this->update(['current_stock' => $newStock]);

            StockMovement::create([
                'product_id' => $this->id,
                'type' => 'dish_sale',
                'quantity' => -$quantity,
                'previous_stock' => $prevStock,
                'new_stock' => $newStock,
                'user_id' => $userId,
                'reference' => $orderReference,
                'notes' => "Venta directa de {$quantity} unidades",
            ]);
        }
    }
}
