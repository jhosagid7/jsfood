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
use Livewire\WithFileUploads;
use App\Models\Bank;
use App\Models\OrderItem;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class CashierRegister extends Component
{
    use WithFileUploads;

    public ?int $selectedOrderId = null;

    // Métodos de pago
    public string $selectedPaymentMethod = 'cash_usd'; // 'cash_usd', 'cash_ved', 'cash_cop', 'card', 'mobile_pay', 'zelle', 'binance_usdt', 'nequi', 'credit'
    public ?int $selectedBankId = null;
    public $proofImage;
    public float $amountReceived = 0.00;
    public string $paymentReference = '';
    public array $appliedPayments = []; // Lista de abonos/pagos parciales para pagos mixtos

    // Cliente
    public ?int $selectedCustomerId = null;

    // Descuentos
    public bool $showDiscountModal = false;
    public string $discountType = 'percent'; // 'percent' o 'fixed'
    public float $discountValue = 0.00;
    public string $discountReason = '';

    // Historial para re-impresión
    public ?int $lastPaidOrderId = null;

    // Detalle de comanda y División por Ítems
    public bool $showItemsDetail = true;
    public bool $showSplitModal = false;
    public string $splitNewAccountLabel = '';
    public array $splitQuantities = [];

    public function mount(?int $order_id = null)
    {
        $orderIdParam = $order_id ?? request()->query('order_id');
        if ($orderIdParam) {
            $order = Order::find((int) $orderIdParam);
            if ($order && !in_array($order->status, ['paid', 'cancelled'])) {
                $this->selectOrder((int) $orderIdParam);
                return;
            } elseif ($order && $order->status === 'paid') {
                $this->lastPaidOrderId = $order->id;
            }
        }

        $firstPending = Order::whereNotIn('status', ['paid', 'cancelled'])->latest()->first();
        if ($firstPending) {
            $this->selectOrder($firstPending->id);
        } else {
            $this->selectedOrderId = null;
            $this->appliedPayments = [];
        }
    }

    public function updatedSelectedPaymentMethod($method)
    {
        $this->proofImage = null;
        $this->paymentReference = '';

        if ($method === 'credit') {
            if (!$this->selectedCustomerId) {
                $this->selectedPaymentMethod = 'cash_usd';
                $this->dispatch('notify', message: "⚠️ Para vender a crédito debes seleccionar primero un cliente con crédito aprobado.", type: 'warning');
                return;
            }

            $customer = Customer::find($this->selectedCustomerId);
            if (!$customer || !$customer->allow_credit) {
                $this->selectedPaymentMethod = 'cash_usd';
                $this->dispatch('notify', message: "❌ El cliente seleccionado no tiene crédito autorizado en el sistema.", type: 'warning');
                return;
            }
        }

        // Auto-asignar banco sugerido según el método
        if ($method === 'mobile_pay') {
            $bank = Bank::where('type', 'pago_movil')->where('state', true)->first();
            $this->selectedBankId = $bank?->id;
        } elseif ($method === 'zelle') {
            $bank = Bank::where('type', 'zelle')->where('state', true)->first();
            $this->selectedBankId = $bank?->id;
        } elseif ($method === 'binance_usdt') {
            $bank = Bank::where('type', 'binance')->where('state', true)->first();
            $this->selectedBankId = $bank?->id;
        } elseif ($method === 'nequi') {
            $bank = Bank::where('type', 'nequi')->where('state', true)->first();
            $this->selectedBankId = $bank?->id;
        } elseif ($method === 'cash_cop') {
            $bank = Bank::where('currency_code', 'COP')->where('state', true)->first();
            $this->selectedBankId = $bank?->id;
        } else {
            $this->selectedBankId = null;
        }

        $this->recalculateRemaining();
    }

    public function selectBank(int $bankId): void
    {
        $bank = Bank::find($bankId);
        if (!$bank) return;

        $this->selectedBankId = $bank->id;
        $this->selectedPaymentMethod = $bank->type;
        $this->proofImage = null;
        $this->paymentReference = '';
        $this->recalculateRemaining();
    }

    public function selectPaymentMethod(string $method, ?int $bankId = null): void
    {
        $this->selectedPaymentMethod = $method;
        $this->selectedBankId = $bankId;
        $this->proofImage = null;
        $this->paymentReference = '';

        if ($method === 'mobile_pay' && !$bankId) {
            $suggested = Bank::where('type', 'pago_movil')->where('state', true)->first();
            $this->selectedBankId = $suggested?->id;
        } elseif ($method === 'zelle' && !$bankId) {
            $suggested = Bank::where('type', 'zelle')->where('state', true)->first();
            $this->selectedBankId = $suggested?->id;
        } elseif ($method === 'nequi' && !$bankId) {
            $suggested = Bank::where('type', 'nequi')->where('state', true)->first();
            $this->selectedBankId = $suggested?->id;
        } elseif ($method === 'binance_usdt' && !$bankId) {
            $suggested = Bank::where('type', 'binance')->where('state', true)->first();
            $this->selectedBankId = $suggested?->id;
        }

        $this->recalculateRemaining();
    }

    public function getActiveCurrencyDetails(): array
    {
        $config = Configuration::first();
        $bcvRate = (float) ($config->bcv_rate ?? 60.00);
        $copRate = (float) ($config->cop_rate ?? 4100.00);
        $binanceRate = (float) ($config->binance_rate ?? 1.00);

        $bank = $this->selectedBankId ? Bank::find($this->selectedBankId) : null;

        if ($bank) {
            $curr = strtoupper(trim($bank->currency_code));
            if (in_array($curr, ['VED', 'BS', 'VES'])) {
                return [
                    'code' => 'VED',
                    'name' => 'Bolívares (BCV)',
                    'symbol' => 'Bs',
                    'rate' => $bcvRate > 0 ? $bcvRate : 60.00,
                    'bank' => $bank,
                ];
            } elseif ($curr === 'COP') {
                return [
                    'code' => 'COP',
                    'name' => 'Pesos Colombianos (COP)',
                    'symbol' => 'COP$',
                    'rate' => $copRate > 0 ? $copRate : 4100.00,
                    'bank' => $bank,
                ];
            } elseif (in_array($curr, ['USDT', 'BINANCE'])) {
                return [
                    'code' => 'USDT',
                    'name' => 'Binance USDT (₮)',
                    'symbol' => '₮',
                    'rate' => $binanceRate > 0 ? $binanceRate : 1.00,
                    'bank' => $bank,
                ];
            } else {
                return [
                    'code' => 'USD',
                    'name' => 'Dólares ($ USD)',
                    'symbol' => '$',
                    'rate' => 1.0000,
                    'bank' => $bank,
                ];
            }
        }

        // Si no hay banco seleccionado, inferir por el método
        if (in_array($this->selectedPaymentMethod, ['cash_ved', 'mobile_pay'])) {
            return [
                'code' => 'VED',
                'name' => 'Bolívares (BCV)',
                'symbol' => 'Bs',
                'rate' => $bcvRate > 0 ? $bcvRate : 60.00,
                'bank' => null,
            ];
        } elseif (in_array($this->selectedPaymentMethod, ['cash_cop', 'nequi'])) {
            return [
                'code' => 'COP',
                'name' => 'Pesos Colombianos (COP)',
                'symbol' => 'COP$',
                'rate' => $copRate > 0 ? $copRate : 4100.00,
                'bank' => null,
            ];
        } elseif ($this->selectedPaymentMethod === 'binance_usdt') {
            return [
                'code' => 'USDT',
                'name' => 'Binance USDT (₮)',
                'symbol' => '₮',
                'rate' => $binanceRate > 0 ? $binanceRate : 1.00,
                'bank' => null,
            ];
        }

        return [
            'code' => 'USD',
            'name' => 'Dólares ($ USD)',
            'symbol' => '$',
            'rate' => 1.0000,
            'bank' => null,
        ];
    }

    public function updatedSelectedBankId($bankId): void
    {
        $this->recalculateRemaining();
    }

    public function updatedSelectedCustomerId($customerId): void
    {
        if (!$customerId) {
            if ($this->selectedPaymentMethod === 'credit') {
                $this->selectedPaymentMethod = 'cash_usd';
            }
            return;
        }

        $customer = Customer::find($customerId);
        if (!$customer || !$customer->allow_credit) {
            if ($this->selectedPaymentMethod === 'credit') {
                $this->selectedPaymentMethod = 'cash_usd';
            }
        }
    }

    public function selectOrder(int $orderId)
    {
        $order = Order::with('items.product', 'diningTable', 'customer', 'payments.bank')->find($orderId);
        if (!$order) return;

        if ($order->status === 'paid') {
            $this->lastPaidOrderId = $order->id;
            $this->selectedOrderId = null;
            $this->appliedPayments = [];
            $this->dispatch('notify', message: "La orden #{$order->order_number} ya fue cobrada y está pagada.", type: 'info');
            return;
        }

        if ($order->status === 'cancelled') {
            $this->selectedOrderId = null;
            $this->appliedPayments = [];
            $this->dispatch('notify', message: "La orden #{$order->order_number} está cancelada.", type: 'warning');
            return;
        }

        $this->selectedOrderId = $orderId;
        $this->selectedCustomerId = $order->customer_id;
        $this->selectedPaymentMethod = 'cash_usd'; // Siempre por defecto Contado
        $this->selectedBankId = null;
        $this->proofImage = null;
        $this->paymentReference = '';
        $this->discountValue = $order->discount_usd > 0 ? (float) $order->discount_usd : 0.00;
        $this->discountType = 'fixed';
        
        // Cargar los abonos/pagos ya registrados en la BD para esta orden
        $this->appliedPayments = $order->payments->map(fn($p) => [
            'id' => $p->id,
            'method' => $p->payment_method,
            'bank_id' => $p->bank_id,
            'bank_name' => $p->bank ? $p->bank->name : null,
            'currency' => $p->currency_code,
            'rate' => (float) $p->exchange_rate,
            'amount_currency' => (float) $p->amount_currency,
            'amount_usd' => (float) $p->amount_usd,
            'reference' => $p->reference ?? '',
            'proof_image' => $p->proof_image,
        ])->toArray();

        $this->recalculateRemaining();
    }

    public function openDiscountModal()
    {
        $order = Order::find($this->selectedOrderId);
        if (!$order) return;
        $this->discountValue = (float) $order->discount_usd;
        $this->discountType = 'fixed';
        $this->showDiscountModal = true;
    }

    public function applyPresetDiscount(float $percent)
    {
        $order = Order::find($this->selectedOrderId);
        if (!$order) return;
        
        $discountUsd = round(($order->subtotal_usd * $percent) / 100, 2);
        $order->applyDiscount($discountUsd);
        $this->discountValue = $discountUsd;
        $this->discountType = 'percent';
        $this->showDiscountModal = false;
        $this->recalculateRemaining();
        $this->dispatch('notify', message: "Descuento del {$percent}% aplicado (-$" . number_format($discountUsd, 2) . ")", type: 'success');
    }

    public function saveDiscount()
    {
        $order = Order::find($this->selectedOrderId);
        if (!$order) return;

        if ($this->discountType === 'percent') {
            $discountUsd = round(($order->subtotal_usd * (float) $this->discountValue) / 100, 2);
        } else {
            $discountUsd = round((float) $this->discountValue, 2);
        }

        if ($discountUsd < 0) {
            $this->dispatch('notify', message: "El descuento no puede ser negativo", type: 'warning');
            return;
        }

        if ($discountUsd > $order->subtotal_usd) {
            $this->dispatch('notify', message: "El descuento no puede superar el subtotal de la orden", type: 'warning');
            return;
        }

        $order->applyDiscount($discountUsd);
        $this->showDiscountModal = false;
        $this->recalculateRemaining();
        $this->dispatch('notify', message: "Descuento de $" . number_format($discountUsd, 2) . " aplicado correctamente", type: 'success');
    }

    public function removeDiscount()
    {
        $order = Order::find($this->selectedOrderId);
        if (!$order) return;

        $order->applyDiscount(0.00);
        $this->discountValue = 0.00;
        $this->showDiscountModal = false;
        $this->recalculateRemaining();
        $this->dispatch('notify', message: "Descuento removido", type: 'info');
    }

    public function cancelSelectedOrder()
    {
        $order = Order::find($this->selectedOrderId);
        if (!$order) return;

        if ($order->payments()->exists()) {
            $this->dispatch('notify', message: "No se puede cancelar una orden con pagos o abonos registrados. Elimina los abonos primero.", type: 'error');
            return;
        }

        if ($order->deleteIfEmpty()) {
            $this->dispatch('notify', message: "Orden vacía eliminada del sistema", type: 'info');
        } else {
            $order->cancel("Cancelada desde Caja");
            $this->dispatch('notify', message: "Orden #{$order->order_number} cancelada exitosamente", type: 'success');
        }

        $this->reset(['selectedOrderId', 'appliedPayments', 'paymentReference', 'discountValue']);
        $this->mount();
    }

    public function addPayment()
    {
        $order = Order::find($this->selectedOrderId);
        if (!$order) return;

        $curr = $this->getActiveCurrencyDetails();
        $rate = $curr['rate'];
        $currencyCode = $curr['code'];
        $bank = $curr['bank'];

        $amountInCurrency = (float) $this->amountReceived;
        if ($amountInCurrency <= 0) {
            $this->dispatch('notify', message: "El monto del pago debe ser mayor a 0", type: 'warning');
            return;
        }

        // Si se ingresó en moneda local, calcular equivalente en USD
        $amountUsd = ($currencyCode === 'USD' || $currencyCode === 'USDT' || $rate <= 0) 
            ? $amountInCurrency 
            : round($amountInCurrency / $rate, 2);

        $cashier = auth()->user() ?? \App\Models\User::where('email', 'cajero@jsfood.dev')->first() ?? \App\Models\User::first();

        // Si no es efectivo ni crédito, requerir comprobante si se paga con pago móvil / zelle
        $isCashOrCredit = in_array($this->selectedPaymentMethod, ['cash_usd', 'cash_ved', 'cash_cop', 'credit']);
        if (!$isCashOrCredit) {
            $this->validate([
                'proofImage' => 'required|image|max:5120',
            ], [
                'proofImage.required' => '⚠️ El capture o comprobante del pago es OBLIGATORIO para transferencias, pago móvil o Zelle.',
            ]);
        }

        // Subir comprobante si fue adjuntado
        $proofPath = null;
        if ($this->proofImage) {
            $this->validate([
                'proofImage' => 'image|max:5120', // Máx 5MB
            ]);
            $proofPath = $this->proofImage->store('proofs', 'public');
        }

        // 1. Guardar de inmediato en la base de datos para que no se pierda al salir
        $payment = Payment::create([
            'order_id' => $order->id,
            'user_id' => $cashier->id,
            'bank_id' => $bank?->id,
            'payment_method' => $this->selectedPaymentMethod,
            'currency_code' => $currencyCode,
            'exchange_rate' => $rate,
            'amount_currency' => $amountInCurrency,
            'amount_usd' => $amountUsd,
            'change_usd' => 0.00,
            'reference' => trim($this->paymentReference),
            'proof_image' => $proofPath,
        ]);

        $this->appliedPayments[] = [
            'id' => $payment->id,
            'method' => $this->selectedPaymentMethod,
            'bank_id' => $bank?->id,
            'bank_name' => $bank?->name,
            'currency' => $currencyCode,
            'rate' => $rate,
            'amount_currency' => $amountInCurrency,
            'amount_usd' => $amountUsd,
            'reference' => trim($this->paymentReference),
            'proof_image' => $proofPath,
        ];

        $this->reset(['paymentReference', 'proofImage']);
        $this->recalculateRemaining();

        $this->dispatch('notify', message: "Abono de {$curr['symbol']} " . number_format($amountInCurrency, 2) . " (\${$amountUsd} USD) guardado exitosamente", type: 'success');
    }

    public function removePayment(int $index)
    {
        if (isset($this->appliedPayments[$index])) {
            $p = $this->appliedPayments[$index];
            if (!empty($p['id'])) {
                Payment::where('id', $p['id'])->where('order_id', $this->selectedOrderId)->delete();
            }
            unset($this->appliedPayments[$index]);
            $this->appliedPayments = array_values($this->appliedPayments);
            $this->recalculateRemaining();
            $this->dispatch('notify', message: "Abono eliminado de la cuenta", type: 'info');
        }
    }

    public function splitEqually(int $persons)
    {
        if ($persons <= 1) return;
        $order = Order::find($this->selectedOrderId);
        if (!$order) return;

        $totalPaidUsd = collect($this->appliedPayments)->sum('amount_usd');
        $baseTotal = ($totalPaidUsd > 0 && ($order->total_usd - $totalPaidUsd) > 0)
            ? ($order->total_usd - $totalPaidUsd)
            : $order->total_usd;

        $amountPerPersonUsd = round($baseTotal / $persons, 2);
        
        $curr = $this->getActiveCurrencyDetails();
        if ($curr['code'] === 'COP') {
            $this->amountReceived = round($amountPerPersonUsd * $curr['rate'], 0);
        } else {
            $this->amountReceived = round($amountPerPersonUsd * $curr['rate'], 2);
        }

        $this->dispatch('notify', message: "Cuenta dividida entre {$persons}: cuota de \${$amountPerPersonUsd} USD ({$curr['symbol']} " . number_format($this->amountReceived, 2) . ") c/u", type: 'info');
    }

    public function toggleItemsDetail(): void
    {
        $this->showItemsDetail = !$this->showItemsDetail;
    }

    public function openSplitModal(): void
    {
        if (!$this->selectedOrderId) return;
        $order = Order::with('items.product')->find($this->selectedOrderId);
        if (!$order || $order->items->isEmpty()) {
            $this->dispatch('notify', message: "La comanda no tiene productos para dividir", type: 'warning');
            return;
        }

        if ($order->payments()->exists()) {
            $this->dispatch('notify', message: "No se puede dividir por platos una orden que ya tiene abonos/pagos registrados. Elimina los abonos primero.", type: 'warning');
            return;
        }

        $table = $order->diningTable;
        $nextNum = $table ? ($table->activeOrders()->count() + 1) : 2;
        $this->splitNewAccountLabel = "Cuenta {$nextNum}";

        $this->splitQuantities = [];
        foreach ($order->items as $item) {
            $this->splitQuantities[$item->id] = 0;
        }

        $this->showSplitModal = true;
    }

    public function incrementSplitQty(int $itemId): void
    {
        $order = Order::with('items')->find($this->selectedOrderId);
        $item = $order?->items->firstWhere('id', $itemId);
        if (!$item) return;

        $current = $this->splitQuantities[$itemId] ?? 0;
        if ($current < $item->quantity) {
            $this->splitQuantities[$itemId] = $current + 1;
        }
    }

    public function decrementSplitQty(int $itemId): void
    {
        $current = $this->splitQuantities[$itemId] ?? 0;
        if ($current > 0) {
            $this->splitQuantities[$itemId] = $current - 1;
        }
    }

    public function executeSplitOrder(): void
    {
        if (!$this->selectedOrderId) return;
        $currentOrder = Order::with('items.product')->find($this->selectedOrderId);
        if (!$currentOrder) return;

        $totalItemsToMove = array_sum($this->splitQuantities);
        if ($totalItemsToMove <= 0) {
            $this->dispatch('notify', message: "Debes seleccionar al menos un producto para mover a la nueva cuenta", type: 'warning');
            return;
        }

        $totalCurrentItems = $currentOrder->items->sum('quantity');
        if ($totalItemsToMove >= $totalCurrentItems) {
            $this->dispatch('notify', message: "No puedes mover todos los platos a la nueva cuenta. Deja al menos un plato en la cuenta actual.", type: 'warning');
            return;
        }

        $label = trim($this->splitNewAccountLabel) ?: 'Sub-cuenta';

        $newOrder = Order::create([
            'table_id' => $currentOrder->table_id,
            'area_id' => $currentOrder->area_id,
            'user_id' => auth()->id() ?? $currentOrder->user_id,
            'customer_id' => null,
            'service_type' => $currentOrder->service_type,
            'account_label' => $label,
            'status' => $currentOrder->status,
        ]);

        foreach ($this->splitQuantities as $itemId => $qtyToMove) {
            if ($qtyToMove <= 0) continue;

            $item = $currentOrder->items->firstWhere('id', $itemId);
            if (!$item) continue;

            if ($qtyToMove >= $item->quantity) {
                $item->order_id = $newOrder->id;
                $item->save();
            } else {
                $item->quantity -= $qtyToMove;
                $item->subtotal_usd = round($item->quantity * $item->unit_price_usd, 2);
                $item->save();

                OrderItem::create([
                    'order_id' => $newOrder->id,
                    'product_id' => $item->product_id,
                    'quantity' => $qtyToMove,
                    'unit_price_usd' => $item->unit_price_usd,
                    'subtotal_usd' => round($qtyToMove * $item->unit_price_usd, 2),
                    'notes' => $item->notes,
                    'kitchen_status' => $item->kitchen_status,
                    'modifiers_data' => $item->modifiers_data,
                ]);
            }
        }

        $currentOrder->calculateTotals();
        $newOrder->calculateTotals();

        if ($currentOrder->diningTable) {
            $currentOrder->diningTable->syncStatus();
        }

        $this->showSplitModal = false;
        $this->splitQuantities = [];
        $this->selectOrder($currentOrder->id);
        $this->dispatch('notify', message: "Cuenta dividida exitosamente. Se creó '{$label}'.", type: 'success');
    }

    private function recalculateRemaining()
    {
        $order = Order::find($this->selectedOrderId);
        if (!$order) return;

        $totalPaidUsd = collect($this->appliedPayments)->sum('amount_usd');
        $remainingUsd = max(0, $order->total_usd - $totalPaidUsd);

        $curr = $this->getActiveCurrencyDetails();

        if ($curr['code'] === 'COP') {
            $this->amountReceived = round($remainingUsd * $curr['rate'], 0);
        } else {
            $this->amountReceived = round($remainingUsd * $curr['rate'], 2);
        }
    }

    public function processCheckout()
    {
        $order = Order::with('items.product', 'diningTable', 'customer', 'payments')->find($this->selectedOrderId);
        if (!$order || $order->items->isEmpty()) {
            $this->dispatch('notify', message: "No hay orden válida para cobrar", type: 'error');
            return;
        }

        // Si se ingresó un monto en amountReceived, registrarlo como abono o pago
        if ((float) $this->amountReceived > 0) {
            $this->addPayment();
            $order->load('payments');
        }

        $totalPaidUsd = collect($this->appliedPayments)->sum('amount_usd');
        // Si aún no se cubre el total (con tolerancia de centavos)
        if ($totalPaidUsd < ($order->total_usd - 0.01)) {
            $faltante = number_format($order->total_usd - $totalPaidUsd, 2);
            $this->dispatch('notify', message: "✅ Abono registrado exitosamente. Resta por pagar: \${$faltante}", type: 'success');
            return;
        }

        $cashier = auth()->user() ?? \App\Models\User::where('email', 'cajero@jsfood.dev')->first() ?? \App\Models\User::first();

        // 1. Manejo de Crédito si aplica
        $hasCredit = collect($this->appliedPayments)->contains('method', 'credit');
        if ($hasCredit) {
            if (!$this->selectedCustomerId) {
                $this->dispatch('notify', message: "⚠️ Debes seleccionar un cliente registrado arriba para compras a crédito (no Consumidor Final)", type: 'error');
                return;
            }

            $customer = Customer::find($this->selectedCustomerId);
            if (!$customer) {
                $this->dispatch('notify', message: "Cliente no encontrado en el sistema", type: 'error');
                return;
            }

            if (!$customer->allow_credit) {
                $this->dispatch('notify', message: "❌ El cliente '{$customer->name}' no tiene crédito habilitado en el sistema", type: 'error');
                return;
            }

            $creditAmount = collect($this->appliedPayments)->where('method', 'credit')->sum('amount_usd');

            if (!$customer->hasAvailableCredit($creditAmount)) {
                $disp = number_format($customer->available_credit, 2);
                $this->dispatch('notify', message: "❌ Límite de crédito excedido. Disponible: \${$disp}, Requerido: \${$creditAmount}", type: 'error');
                return;
            }

            $customer->adjustBalance($creditAmount, 'credit_charge', $order->id, $cashier->id, 'ORD-' . $order->order_number, "Cargo por comanda {$order->order_number}");
        }

        // 2. Si hubo cambio o vuelto, registrarlo en el último pago
        $changeUsd = max(0, $totalPaidUsd - $order->total_usd);
        if ($changeUsd > 0 && !empty($this->appliedPayments)) {
            $lastIndex = count($this->appliedPayments) - 1;
            $lastPaymentId = $this->appliedPayments[$lastIndex]['id'] ?? null;
            if ($lastPaymentId) {
                Payment::where('id', $lastPaymentId)->update(['change_usd' => $changeUsd]);
            }
        }

        // 3. Cerrar y pagar la orden (descuenta stock e insumos y sincroniza mesa)
        $order->customer_id = $this->selectedCustomerId;
        $order->closeAndPay($cashier->id);

        // 4. Imprimir Ticket Térmico de Caja (58/80mm) con Apertura de Gaveta
        $config = Configuration::first();
        $printerWidth = $config->printer_width ?? 80;
        $printerName = $config->printer_name ?? 'POS-80';
        $escpos = TicketPrinterService::buildReceiptTicket($order, $printerWidth, true);
        $printed = TicketPrinterService::sendToPrinter($escpos, $printerName);

        $this->lastPaidOrderId = $order->id;

        if ($printed) {
            $this->dispatch('notify', message: "✅ Cobro procesado exitosamente. Ticket impreso en {$printerName}.", type: 'success');
        } else {
            $this->dispatch('notify', message: "✅ Cobro procesado exitosamente, pero la impresora '{$printerName}' no respondió. Revisa la conexión de la impresora.", type: 'warning');
        }

        // Resetear y seleccionar siguiente orden
        $this->reset(['selectedOrderId', 'appliedPayments', 'paymentReference', 'discountValue', 'selectedCustomerId']);
        $this->selectedPaymentMethod = 'cash_usd';
        $this->dispatch('clear-order-query');

        $nextPending = Order::whereNotIn('status', ['paid', 'cancelled'])->latest()->first();
        if ($nextPending) {
            $this->selectOrder($nextPending->id);
        }
    }

    public function reprintTicket(?int $orderId = null)
    {
        $id = $orderId ?? $this->lastPaidOrderId;
        if (!$id) {
            $this->dispatch('notify', message: "No hay ticket reciente para re-imprimir", type: 'warning');
            return;
        }

        $order = Order::with('items.product', 'diningTable', 'customer', 'payments')->find($id);
        if (!$order) {
            $this->dispatch('notify', message: "Orden no encontrada", type: 'error');
            return;
        }

        $config = Configuration::first();
        $printerWidth = $config->printer_width ?? 80;
        $printerName = $config->printer_name ?? 'POS-80';
        $escpos = TicketPrinterService::buildReceiptTicket($order, $printerWidth, false);
        $printed = TicketPrinterService::sendToPrinter($escpos, $printerName);

        if ($printed) {
            $this->dispatch('notify', message: "Ticket #{$order->order_number} re-impreso exitosamente en {$printerName}", type: 'success');
        } else {
            $this->dispatch('notify', message: "Error al re-imprimir: la impresora '{$printerName}' no respondió", type: 'error');
        }
    }

    public function render()
    {
        $pendingOrders = Order::with(['diningTable', 'customer', 'items.product', 'payments'])
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->orderBy('created_at', 'desc')
            ->get();

        $selectedOrder = $this->selectedOrderId ? Order::with(['diningTable', 'customer', 'items.product'])->find($this->selectedOrderId) : null;
        $customers = Customer::orderBy('name')->get();
        $config = Configuration::first();
        $banks = Bank::where('state', true)->orderBy('sort')->get();

        return view('livewire.cashier.cashier-register', [
            'pendingOrders' => $pendingOrders,
            'selectedOrder' => $selectedOrder,
            'customers' => $customers,
            'config' => $config,
            'banks' => $banks,
        ]);
    }
}
