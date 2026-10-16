<?php

namespace App\Livewire\Cashier;

use Livewire\Component;
use App\Models\Order;
use App\Models\Table;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Configuration;
use App\Helpers\CurrencyHelper;
use App\Services\TicketPrinterService;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class CashierRegister extends Component
{
    public ?int $selectedOrderId = null;

    // Métodos de pago
    public string $selectedPaymentMethod = 'cash_usd'; // 'cash_usd', 'cash_ved', 'cash_cop', 'card', 'mobile_pay', 'zelle', 'binance_usdt', 'credit'
    public float $amountReceived = 0.00;
    public string $paymentReference = '';
    public array $appliedPayments = []; // Lista de abonos/pagos parciales para pagos mixtos

    // Cliente
    public ?int $selectedCustomerId = null;

    public function mount()
    {
        $orderIdParam = request()->query('order_id');
        if ($orderIdParam) {
            $this->selectOrder((int) $orderIdParam);
        } else {
            $firstPending = Order::whereNotIn('status', ['paid', 'cancelled'])->latest()->first();
            if ($firstPending) {
                $this->selectOrder($firstPending->id);
            }
        }
    }

    public function selectOrder(int $orderId)
    {
        $this->selectedOrderId = $orderId;
        $order = Order::with('items.product', 'diningTable', 'customer')->find($orderId);
        if ($order) {
            $this->selectedCustomerId = $order->customer_id;
            $this->amountReceived = $order->total_usd;
            $this->appliedPayments = [];
        }
    }

    public function addPayment()
    {
        $order = Order::find($this->selectedOrderId);
        if (!$order) return;

        $config = Configuration::first();
        $rate = 1.0000;
        $currencyCode = 'USD';

        if ($this->selectedPaymentMethod === 'cash_ved' || $this->selectedPaymentMethod === 'mobile_pay') {
            $rate = $config->bcv_rate ?? 60.00;
            $currencyCode = 'VED';
        } elseif ($this->selectedPaymentMethod === 'cash_cop') {
            $rate = $config->cop_rate ?? 4100.00;
            $currencyCode = 'COP';
        } elseif ($this->selectedPaymentMethod === 'binance_usdt') {
            $rate = $config->binance_rate ?? 1.00;
            $currencyCode = 'USDT';
        }

        // Si se ingresó en moneda local, calcular equivalente en USD
        $amountInCurrency = $this->amountReceived;
        $amountUsd = ($currencyCode === 'USD' || $currencyCode === 'USDT') ? $amountInCurrency : ($amountInCurrency / $rate);

        $this->appliedPayments[] = [
            'method' => $this->selectedPaymentMethod,
            'currency' => $currencyCode,
            'rate' => $rate,
            'amount_currency' => $amountInCurrency,
            'amount_usd' => round($amountUsd, 2),
            'reference' => trim($this->paymentReference),
        ];

        $this->reset(['paymentReference']);
        $this->recalculateRemaining();
    }

    public function removePayment(int $index)
    {
        if (isset($this->appliedPayments[$index])) {
            unset($this->appliedPayments[$index]);
            $this->appliedPayments = array_values($this->appliedPayments);
            $this->recalculateRemaining();
        }
    }

    private function recalculateRemaining()
    {
        $order = Order::find($this->selectedOrderId);
        if (!$order) return;

        $totalPaidUsd = collect($this->appliedPayments)->sum('amount_usd');
        $remaining = max(0, $order->total_usd - $totalPaidUsd);
        $this->amountReceived = round($remaining, 2);
    }

    public function processCheckout()
    {
        $order = Order::with('items.product', 'diningTable', 'customer')->find($this->selectedOrderId);
        if (!$order || $order->items->isEmpty()) {
            $this->dispatch('notify', message: "No hay orden válida para cobrar", type: 'error');
            return;
        }

        // Si no se agregaron pagos mixtos manuales, registrar el total con el método seleccionado
        if (empty($this->appliedPayments)) {
            $this->addPayment();
        }

        $totalPaidUsd = collect($this->appliedPayments)->sum('amount_usd');
        if ($totalPaidUsd < $order->total_usd) {
            $faltante = number_format($order->total_usd - $totalPaidUsd, 2);
            $this->dispatch('notify', message: "Monto insuficiente. Falta por cubrir: \${$faltante}", type: 'warning');
            return;
        }

        $cashier = auth()->user() ?? \App\Models\User::where('email', 'cajero@jsfood.dev')->first() ?? \App\Models\User::first();

        // 1. Manejo de Crédito si aplica
        $hasCredit = collect($this->appliedPayments)->contains('method', 'credit');
        if ($hasCredit) {
            if (!$this->selectedCustomerId) {
                $this->dispatch('notify', message: "Debes seleccionar un cliente registrado para compras a crédito", type: 'error');
                return;
            }

            $customer = Customer::find($this->selectedCustomerId);
            $creditAmount = collect($this->appliedPayments)->where('method', 'credit')->sum('amount_usd');

            if (!$customer->hasAvailableCredit($creditAmount)) {
                $this->dispatch('notify', message: "Límite de crédito excedido para el cliente", type: 'error');
                return;
            }

            $customer->adjustBalance($creditAmount, 'credit_charge', $order->id, $cashier->id, 'ORD-' . $order->order_number, "Cargo por orden {$order->order_number}");
        }

        // 2. Registrar los pagos
        foreach ($this->appliedPayments as $p) {
            Payment::create([
                'order_id' => $order->id,
                'user_id' => $cashier->id,
                'payment_method' => $p['method'],
                'currency_code' => $p['currency'],
                'exchange_rate' => $p['rate'],
                'amount_currency' => $p['amount_currency'],
                'amount_usd' => $p['amount_usd'],
                'change_usd' => max(0, $totalPaidUsd - $order->total_usd),
                'reference' => $p['reference'],
            ]);
        }

        // 3. Cerrar y pagar la orden (descuenta stock e insumos y sincroniza mesa)
        $order->customer_id = $this->selectedCustomerId;
        $order->closeAndPay($cashier->id);

        // 4. Imprimir Ticket Térmico de Caja (58/80mm) con Apertura de Gaveta
        $config = Configuration::first();
        $printerWidth = $config->printer_width ?? 80;
        $escpos = TicketPrinterService::buildReceiptTicket($order, $printerWidth, true);
        TicketPrinterService::sendToPrinter($escpos, $config->printer_name ?? 'POS-80');

        $this->dispatch('notify', message: "✅ Cobro procesado exitosamente. Ticket impreso.", type: 'success');

        // Resetear y seleccionar siguiente orden
        $this->reset(['selectedOrderId', 'appliedPayments', 'paymentReference']);
        $this->mount();
    }

    public function render()
    {
        $pendingOrders = Order::with(['diningTable', 'customer', 'items.product'])
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->orderBy('created_at', 'desc')
            ->get();

        $selectedOrder = $this->selectedOrderId ? Order::with(['diningTable', 'customer', 'items.product'])->find($this->selectedOrderId) : null;
        $customers = Customer::orderBy('name')->get();
        $config = Configuration::first();

        return view('livewire.cashier.cashier-register', [
            'pendingOrders' => $pendingOrders,
            'selectedOrder' => $selectedOrder,
            'customers' => $customers,
            'config' => $config,
        ]);
    }
}
