<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Carbon\Carbon;

class Customer extends Model
{
    use HasFactory, Notifiable;

    protected $guarded = ['id'];

    protected $casts = [
        'allow_credit' => 'boolean',
        'credit_limit' => 'float',
        'credit_days' => 'integer',
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
     * Margen de crédito disponible restante.
     */
    public function getAvailableCreditAttribute(): float
    {
        if (!$this->allow_credit) {
            return 0.00;
        }

        return max(0.00, (float) $this->credit_limit - (float) $this->current_balance);
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
     * Fecha de vencimiento estimada del saldo deudor más antiguo.
     */
    public function getCreditDueDateAttribute(): ?Carbon
    {
        if ($this->current_balance <= 0) {
            return null;
        }

        $oldestUnpaidCharge = $this->transactions()
            ->where('type', 'credit_charge')
            ->orderBy('created_at', 'desc')
            ->first();

        $baseDate = $oldestUnpaidCharge ? $oldestUnpaidCharge->created_at : $this->updated_at;
        $days = $this->credit_days > 0 ? $this->credit_days : 15;

        return Carbon::parse($baseDate)->addDays($days);
    }

    /**
     * Estado del crédito:
     * - 'no_debt': Saldo en 0
     * - 'active': Al día dentro del plazo
     * - 'due_soon': Vence en los próximos 3 días
     * - 'overdue': Plazo vencido con saldo pendiente
     */
    public function getCreditStatusAttribute(): string
    {
        if ($this->current_balance <= 0) {
            return 'no_debt';
        }

        $dueDate = $this->credit_due_date;
        if (!$dueDate) {
            return 'active';
        }

        $now = now();
        if ($now->greaterThan($dueDate)) {
            return 'overdue';
        }

        if ($now->diffInDays($dueDate, false) <= 3) {
            return 'due_soon';
        }

        return 'active';
    }

    /**
     * Días de vencimiento o días restantes.
     */
    public function getDaysRemainingAttribute(): int
    {
        $dueDate = $this->credit_due_date;
        if (!$dueDate) {
            return 0;
        }

        return (int) now()->diffInDays($dueDate, false);
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
