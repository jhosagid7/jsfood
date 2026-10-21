<?php

namespace App\Livewire\Customers;

use Livewire\Component;
use Livewire\WithPagination;
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
    public string $abonoMethod = 'cash_usd';
    public ?int $abonoBankId = null;
    public string $abonoReference = '';
    public string $abonoNotes = '';

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
        $this->abonoReference = '';
        $this->abonoNotes = '';
        $this->showAbonoModal = true;
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

        if ($this->abonoAmountUsd > $customer->current_balance + 0.01) {
            $this->dispatch('notify', message: "El monto a abonar no puede exceder la deuda de \${$customer->current_balance}", type: 'warning');
            return;
        }

        $user = auth()->user();
        $bankName = $this->abonoBankId ? Bank::find($this->abonoBankId)?->name : null;
        $notes = "Método: {$this->abonoMethod}" . ($bankName ? " ({$bankName})" : '') . ($this->abonoNotes ? " - {$this->abonoNotes}" : '');

        // Registrar abono
        $tx = $customer->adjustBalance(
            -$this->abonoAmountUsd,
            'payment_abono',
            null,
            $user?->id,
            $this->abonoReference ?: 'Abono en Caja',
            $notes
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
        $this->dispatch('notify', message: "Abono de \${$this->abonoAmountUsd} registrado exitosamente para {$customer->name}", type: 'success');
        $this->reset(['abonoCustomerId', 'abonoCustomer', 'abonoAmountUsd', 'abonoReference', 'abonoNotes']);
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
