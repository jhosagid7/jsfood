<?php

namespace App\Livewire\Customers;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;
use App\Models\Customer;
use App\Models\Bank;
use App\Models\Configuration;
use App\Notifications\CreditPaymentReceivedNotification;
use App\Notifications\CreditPaymentReminderNotification;
use Illuminate\Support\Facades\Log;

#[Layout('layouts.app')]
class CustomerManager extends Component
{
    use WithPagination;
    use WithFileUploads;

    // Búsqueda y Filtros
    public string $search = '';
    public string $filter = 'all'; // 'all', 'with_debt', 'overdue', 'credit_allowed'

    // Modal Crear / Editar Cliente
    public bool $showCustomerModal = false;
    public ?int $customerId = null;
    public string $name = '';
    public string $identification = '';
    public string $phone = '';
    public string $email = '';
    public string $address = '';
    public bool $allow_credit = false;
    public float $credit_limit = 0.00;
    public int $credit_days = 15;

    // Modal Registrar Abono
    public bool $showAbonoModal = false;
    public ?int $abonoCustomerId = null;
    public ?Customer $abonoCustomer = null;
    public float $abonoAmountUsd = 0.00;
    public float $abonoAmountCurrency = 0.00;
    public string $abonoCurrency = 'USD'; // 'USD', 'VED', 'COP', 'USDT'
    public float $abonoExchangeRate = 1.0000;
    public string $abonoMethod = 'cash_usd';
    public ?int $abonoBankId = null;
    public string $abonoReference = '';
    public string $abonoNotes = '';
    public $abonoProof = null;

    // Modal Historial / Estado de Cuenta
    public bool $showHistoryModal = false;
    public ?Customer $historyCustomer = null;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:150',
            'identification' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:150',
            'address' => 'nullable|string|max:255',
            'allow_credit' => 'boolean',
            'credit_limit' => 'numeric|min:0',
            'credit_days' => 'integer|min:1|max:365',
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->reset(['customerId', 'name', 'identification', 'phone', 'email', 'address', 'allow_credit', 'credit_limit']);
        $this->credit_days = 15;
        $this->showCustomerModal = true;
    }

    public function editCustomer(int $id): void
    {
        $customer = Customer::find($id);
        if (!$customer) return;

        $this->customerId = $customer->id;
        $this->name = $customer->name;
        $this->identification = $customer->identification ?? '';
        $this->phone = $customer->phone ?? '';
        $this->email = $customer->email ?? '';
        $this->address = $customer->address ?? '';
        $this->allow_credit = (bool) $customer->allow_credit;
        $this->credit_limit = (float) $customer->credit_limit;
        $this->credit_days = (int) ($customer->credit_days ?? 15);

        $this->showCustomerModal = true;
    }

    public function saveCustomer(): void
    {
        $this->validate();

        $data = [
            'name' => trim($this->name),
            'identification' => trim($this->identification) ?: null,
            'phone' => trim($this->phone) ?: null,
            'email' => trim($this->email) ?: null,
            'address' => trim($this->address) ?: null,
            'allow_credit' => $this->allow_credit,
            'credit_limit' => $this->allow_credit ? $this->credit_limit : 0.00,
            'credit_days' => $this->allow_credit ? $this->credit_days : 15,
        ];

        if ($this->customerId) {
            $customer = Customer::find($this->customerId);
            if ($customer) {
                $customer->update($data);
                $this->dispatch('notify', message: "Cliente '{$customer->name}' actualizado con éxito", type: 'success');
            }
        } else {
            Customer::create($data);
            $this->dispatch('notify', message: "Nuevo cliente '{$data['name']}' registrado", type: 'success');
        }

        $this->showCustomerModal = false;
        $this->reset(['customerId', 'name', 'identification', 'phone', 'email', 'address', 'allow_credit', 'credit_limit']);
    }

    public function openAbonoModal(int $id): void
    {
        $customer = Customer::find($id);
        if (!$customer) return;

        if ($customer->current_balance <= 0) {
            $this->dispatch('notify', message: "El cliente '{$customer->name}' no posee saldo deudor pendiente.", type: 'info');
            return;
        }

        $this->abonoCustomerId = $customer->id;
        $this->abonoCustomer = $customer;
        $this->abonoAmountUsd = (float) $customer->current_balance;
        $this->abonoMethod = 'cash_usd';
        $this->abonoBankId = null;
        $this->abonoCurrency = 'USD';
        $this->abonoExchangeRate = 1.0000;
        $this->abonoAmountCurrency = (float) $customer->current_balance;
        $this->abonoReference = '';
        $this->abonoNotes = '';
        $this->abonoProof = null;
        $this->showAbonoModal = true;
    }

    public function setAbonoMethod(string $method): void
    {
        $this->abonoMethod = $method;
        $this->syncAbonoCurrencyAndRates();
    }

    public function updatedAbonoMethod($method): void
    {
        $this->syncAbonoCurrencyAndRates();
    }

    public function updatedAbonoBankId($bankId): void
    {
        $this->syncAbonoCurrencyAndRates();
    }

    private function syncAbonoCurrencyAndRates(): void
    {
        $config = Configuration::first();
        $bcvRate = (float) ($config->bcv_rate ?? 60.00);
        $copRate = (float) ($config->cop_rate ?? 4100.00);
        $binanceRate = (float) ($config->binance_rate ?? 1.00);

        // Si se seleccionó un banco, tomar la moneda del banco si existe
        $bank = $this->abonoBankId ? Bank::find($this->abonoBankId) : null;

        if ($bank && in_array(strtoupper($bank->currency_code), ['VED', 'BS', 'VES'])) {
            $this->abonoCurrency = 'VED';
            $this->abonoExchangeRate = $bcvRate > 0 ? $bcvRate : 60.00;
        } elseif ($bank && strtoupper($bank->currency_code) === 'COP') {
            $this->abonoCurrency = 'COP';
            $this->abonoExchangeRate = $copRate > 0 ? $copRate : 4100.00;
        } elseif ($bank && in_array(strtoupper($bank->currency_code), ['USDT', 'BINANCE'])) {
            $this->abonoCurrency = 'USDT';
            $this->abonoExchangeRate = $binanceRate > 0 ? $binanceRate : 1.00;
        } elseif ($this->abonoMethod === 'cash_bs' || $this->abonoMethod === 'pago_movil') {
            $this->abonoCurrency = 'VED';
            $this->abonoExchangeRate = $bcvRate > 0 ? $bcvRate : 60.00;
            if (!$this->abonoBankId && $this->abonoMethod === 'pago_movil') {
                $suggestedBank = Bank::where('type', 'pago_movil')->where('state', true)->first();
                $this->abonoBankId = $suggestedBank?->id;
            }
        } elseif ($this->abonoMethod === 'cash_cop' || $this->abonoMethod === 'nequi') {
            $this->abonoCurrency = 'COP';
            $this->abonoExchangeRate = $copRate > 0 ? $copRate : 4100.00;
            if (!$this->abonoBankId && $this->abonoMethod === 'nequi') {
                $suggestedBank = Bank::where('type', 'nequi')->where('state', true)->first();
                $this->abonoBankId = $suggestedBank?->id;
            }
        } elseif ($this->abonoMethod === 'binance_usdt') {
            $this->abonoCurrency = 'USDT';
            $this->abonoExchangeRate = $binanceRate > 0 ? $binanceRate : 1.00;
            if (!$this->abonoBankId) {
                $suggestedBank = Bank::where('type', 'binance')->where('state', true)->first();
                $this->abonoBankId = $suggestedBank?->id;
            }
        } elseif ($this->abonoMethod === 'zelle') {
            $this->abonoCurrency = 'USD';
            $this->abonoExchangeRate = 1.0000;
            if (!$this->abonoBankId) {
                $suggestedBank = Bank::where('type', 'zelle')->where('state', true)->first();
                $this->abonoBankId = $suggestedBank?->id;
            }
        } else {
            $this->abonoCurrency = 'USD';
            $this->abonoExchangeRate = 1.0000;
        }

        // Recalcular monto en la moneda elegida
        if ($this->abonoCurrency === 'COP') {
            $this->abonoAmountCurrency = round($this->abonoAmountUsd * $this->abonoExchangeRate, 0);
        } else {
            $this->abonoAmountCurrency = round($this->abonoAmountUsd * $this->abonoExchangeRate, 2);
        }
    }

    public function updatedAbonoAmountCurrency($val): void
    {
        $amount = (float) $val;
        if ($this->abonoExchangeRate > 0) {
            $this->abonoAmountUsd = round($amount / $this->abonoExchangeRate, 2);
        } else {
            $this->abonoAmountUsd = round($amount, 2);
        }
    }

    public function updatedAbonoAmountUsd($val): void
    {
        $amountUsd = (float) $val;
        if ($this->abonoCurrency === 'COP') {
            $this->abonoAmountCurrency = round($amountUsd * $this->abonoExchangeRate, 0);
        } else {
            $this->abonoAmountCurrency = round($amountUsd * $this->abonoExchangeRate, 2);
        }
    }

    public function processAbono(): void
    {
        if (!$this->abonoCustomerId) return;

        $customer = Customer::find($this->abonoCustomerId);
        if (!$customer) return;

        if ($this->abonoAmountUsd <= 0) {
            $this->dispatch('notify', message: "Ingresa un monto de abono mayor a 0", type: 'warning');
            return;
        }

        if ($this->abonoAmountUsd > $customer->current_balance + 0.05) {
            $this->dispatch('notify', message: "El monto a abonar no puede exceder la deuda de \${$customer->current_balance}", type: 'warning');
            return;
        }

        $isCash = in_array($this->abonoMethod, ['cash_usd', 'cash_bs', 'cash_cop']);

        // Validación obligatoria de comprobante si no es efectivo
        if (!$isCash) {
            $this->validate([
                'abonoProof' => 'required|image|max:5120',
            ], [
                'abonoProof.required' => '⚠️ El capture o comprobante del pago es OBLIGATORIO para transferencias, pago móvil o punto de venta.',
                'abonoProof.image' => 'El comprobante debe ser una imagen válida (JPG, PNG, WEBP).',
                'abonoProof.max' => 'El comprobante no debe superar los 5MB de tamaño.',
            ]);
        } else {
            if ($this->abonoProof) {
                $this->validate([
                    'abonoProof' => 'image|max:5120',
                ]);
            }
        }

        $proofPath = null;
        if ($this->abonoProof) {
            $proofPath = $this->abonoProof->store('proofs', 'public');
        }

        $finalAmountUsd = min($this->abonoAmountUsd, (float) $customer->current_balance);

        $user = auth()->user();
        $bank = $this->abonoBankId ? Bank::find($this->abonoBankId) : null;
        $bankName = $bank?->name;

        $currencySymbol = match($this->abonoCurrency) {
            'VED' => 'Bs',
            'COP' => 'COP$',
            'USDT' => 'USDT',
            default => '$',
        };

        $notes = "Método: {$this->abonoMethod}" 
            . ($bankName ? " ({$bankName} - {$this->abonoCurrency})" : '') 
            . " [Abonado: {$currencySymbol} " . number_format($this->abonoAmountCurrency, 2) . " a tasa " . number_format($this->abonoExchangeRate, 2) . "]"
            . ($this->abonoNotes ? " - {$this->abonoNotes}" : '');

        // Registrar abono
        $tx = $customer->adjustBalance(
            -$finalAmountUsd,
            'payment_abono',
            null,
            $user?->id,
            $this->abonoReference ?: 'Abono en Caja',
            $notes,
            $this->abonoCurrency,
            $this->abonoAmountCurrency,
            $proofPath
        );

        // Envío de correo en segundo plano
        if (!empty($customer->email)) {
            try {
                $customer->notify(new CreditPaymentReceivedNotification($tx, (float) $customer->current_balance));
            } catch (\Throwable $e) {
                Log::warning("No se pudo enviar correo de abono a {$customer->email}: {$e->getMessage()}");
            }
        }

        $this->showAbonoModal = false;
        $this->dispatch('notify', message: "✅ Abono de \${$finalAmountUsd} ({$currencySymbol} " . number_format($this->abonoAmountCurrency, 2) . ") registrado exitosamente para {$customer->name}", type: 'success');
        $this->reset(['abonoCustomerId', 'abonoCustomer', 'abonoAmountUsd', 'abonoAmountCurrency', 'abonoReference', 'abonoNotes', 'abonoProof']);
    }

    public function openHistoryModal(int $id): void
    {
        $this->historyCustomer = Customer::with(['transactions.order', 'transactions.user'])->find($id);
        if ($this->historyCustomer) {
            $this->showHistoryModal = true;
        }
    }

    public function sendReminderEmail(int $id): void
    {
        $customer = Customer::find($id);
        if (!$customer) return;

        if (empty($customer->email)) {
            $this->dispatch('notify', message: "El cliente {$customer->name} no tiene correo electrónico registrado", type: 'warning');
            return;
        }

        if ($customer->current_balance <= 0) {
            $this->dispatch('notify', message: "El cliente {$customer->name} está al día, no requiere cobro.", type: 'info');
            return;
        }

        $isOverdue = $customer->credit_status === 'overdue';
        $dueDateStr = $customer->credit_due_date ? $customer->credit_due_date->format('d/m/Y') : null;

        try {
            $customer->notify(new CreditPaymentReminderNotification((float) $customer->current_balance, $dueDateStr, $isOverdue));
            $this->dispatch('notify', message: "Recordatorio de pago enviado a {$customer->email}", type: 'success');
        } catch (\Throwable $e) {
            Log::warning("Error enviando recordatorio a {$customer->email}: {$e->getMessage()}");
            $this->dispatch('notify', message: "No se pudo despachar el correo (Verifica configuración SMTP)", type: 'warning');
        }
    }

    public function deleteCustomer(int $id): void
    {
        $customer = Customer::find($id);
        if (!$customer) return;

        if ($customer->current_balance > 0) {
            $this->dispatch('notify', message: "No puedes eliminar a un cliente con saldo deudor pendiente.", type: 'error');
            return;
        }

        $customer->delete();
        $this->dispatch('notify', message: "Cliente eliminado correctamente", type: 'success');
    }

    public function render()
    {
        $query = Customer::query();

        // Búsqueda
        if (!empty($this->search)) {
            $s = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', $s)
                  ->orWhere('identification', 'like', $s)
                  ->orWhere('phone', 'like', $s)
                  ->orWhere('email', 'like', $s);
            });
        }

        // Filtro
        if ($this->filter === 'with_debt') {
            $query->where('current_balance', '>', 0);
        } elseif ($this->filter === 'credit_allowed') {
            $query->where('allow_credit', true);
        }

        $customers = $query->orderBy('name')->paginate(15);

        // Si el filtro es por vencimiento, filtramos la colección por estado
        if ($this->filter === 'overdue') {
            $customers = Customer::where('current_balance', '>', 0)
                ->get()
                ->filter(fn($c) => $c->credit_status === 'overdue');
        }

        // Métricas Globales
        $allCustomers = Customer::all();
        $totalCustomersCount = $allCustomers->count();
        $totalDebtUsd = (float) $allCustomers->sum('current_balance');
        $customersWithDebtCount = $allCustomers->where('current_balance', '>', 0)->count();
        $overdueCount = $allCustomers->filter(fn($c) => $c->credit_status === 'overdue')->count();

        $banks = Bank::where('state', true)->orderBy('sort')->get();
        $config = Configuration::first();

        return view('livewire.customers.customer-manager', [
            'customers' => $customers,
            'totalCustomersCount' => $totalCustomersCount,
            'totalDebtUsd' => $totalDebtUsd,
            'customersWithDebtCount' => $customersWithDebtCount,
            'overdueCount' => $overdueCount,
            'banks' => $banks,
            'config' => $config,
        ]);
    }
}
