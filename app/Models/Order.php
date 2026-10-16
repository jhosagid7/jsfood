<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'subtotal_usd' => 'float',
        'discount_usd' => 'float',
        'tax_usd' => 'float',
        'total_usd' => 'float',
        'kitchen_sent_at' => 'datetime',
        'kitchen_ready_at' => 'datetime',
        'served_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    protected static function booted()
    {
        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = 'ORD-' . strtoupper(Str::random(6));
            }
        });
    }

    public function diningTable(): BelongsTo
    {
        return $this->belongsTo(Table::class, 'table_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Recalcular los totales de la orden.
     */
    public function calculateTotals(): void
    {
        $subtotal = $this->items()->sum('subtotal_usd');
        $this->subtotal_usd = $subtotal;
        $this->total_usd = max(0, $subtotal - $this->discount_usd + $this->tax_usd);
        $this->save();
    }

    /**
     * Enviar comanda a cocina: actualiza tiempos y estados de items.
     */
    public function sendToKitchen(): void
    {
        $this->status = 'in_kitchen';
        $this->kitchen_sent_at = now();
        $this->save();

        $this->items()->where('kitchen_status', 'pending')->update([
            'kitchen_status' => 'preparing',
        ]);

        if ($this->diningTable) {
            $this->diningTable->syncStatus();
        }
    }

    /**
     * Marcar orden lista para servir desde cocina (KDS).
     */
    public function markReady(): void
    {
        $this->status = 'ready';
        $this->kitchen_ready_at = now();
        $this->save();

        $this->items()->update(['kitchen_status' => 'ready']);

        if ($this->diningTable) {
            $this->diningTable->syncStatus();
        }
    }

    /**
     * Marcar como servida por el mesonero.
     */
    public function markServed(): void
    {
        $this->status = 'served';
        $this->served_at = now();
        $this->save();

        $this->items()->update(['kitchen_status' => 'served']);

        if ($this->diningTable) {
            $this->diningTable->syncStatus();
        }
    }

    /**
     * Cerrar y pagar la orden, descontando stock de los platos e insumos.
     */
    public function closeAndPay(?int $cashierId = null): void
    {
        $this->status = 'paid';
        $this->payment_status = 'paid';
        $this->cashier_id = $cashierId;
        $this->closed_at = now();
        $this->save();

        // Descontar inventario de cada ítem de la comanda
        foreach ($this->items()->with('product.recipeItems.supply')->get() as $item) {
            if ($item->product) {
                $item->product->deductStockForSale($item->quantity, $cashierId, $this->order_number);
            }
        }

        // Sincronizar mesa: si no quedan más cuentas activas en la mesa, pasa a libre
        if ($this->diningTable) {
            $this->diningTable->syncStatus();
        }
    }
}
