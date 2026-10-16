<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Table extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function activeOrders(): HasMany
    {
        return $this->hasMany(Order::class)->whereNotIn('status', ['paid', 'cancelled']);
    }

    /**
     * Recalcular y sincronizar el estado visual de la mesa en base a sus subcuentas activas.
     */
    public function syncStatus(): void
    {
        $active = $this->activeOrders()->get();
        $count = $active->count();
        $this->active_orders_count = $count;

        if ($count === 0) {
            $this->status = 'free';
        } else {
            // Si alguna cuenta tiene 'in_kitchen', prioridad cocina
            if ($active->contains('status', 'in_kitchen')) {
                $this->status = 'kitchen';
            } elseif ($active->contains('status', 'ready')) {
                $this->status = 'ready';
            } elseif ($active->contains('status', 'bill_requested')) {
                $this->status = 'bill_requested';
            } elseif ($active->contains('status', 'served')) {
                $this->status = 'served';
            } else {
                $this->status = 'occupied';
            }
        }

        $this->save();
    }
}
