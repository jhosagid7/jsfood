<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'allow_credit' => 'boolean',
        'credit_limit' => 'float',
        'current_balance' => 'float',
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(CustomerTransaction::class)->orderByDesc('created_at');
    }

    /**
     * Verificar si tiene margen de crédito disponible.
     */
    public function hasAvailableCredit(float $amountUsd): bool
    {
        if (!$this->allow_credit) {
            return false;
        }

        return ($this->current_balance + $amountUsd) <= $this->credit_limit;
    }

    /**
     * Registrar un cargo o abono a su cuenta corriente.
     */
    public function adjustBalance(float $deltaUsd, string $type, ?int $orderId = null, ?int $userId = null, ?string $reference = null, ?string $notes = null): CustomerTransaction
    {
        $this->current_balance += $deltaUsd;
        $this->save();

        return CustomerTransaction::create([
            'customer_id' => $this->id,
            'order_id' => $orderId,
            'user_id' => $userId,
            'type' => $type,
            'amount_usd' => abs($deltaUsd),
            'currency_code' => 'USD',
            'amount_currency' => abs($deltaUsd),
            'reference' => $reference,
            'notes' => $notes,
        ]);
    }
}
