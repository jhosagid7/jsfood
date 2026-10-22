<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Bank;
use App\Models\Configuration;
use App\Models\Currency;

#[Layout('layouts.app')]
class BankManager extends Component
{
    // Tasas de Cambio Globales
    public float $bcvRate = 60.00;
    public float $copRate = 4100.00;
    public float $binanceRate = 1.00;
    public string $currencyMode = 'multi';

    // Modal Crear / Editar Cuenta Bancaria
    public bool $showBankModal = false;
    public ?int $bankId = null;
    public string $name = '';
    public string $currency_code = 'VED'; // 'VED', 'COP', 'USD', 'USDT'
    public string $type = 'bank_transfer'; // 'bank_transfer', 'pago_movil', 'card', 'zelle', 'binance', 'nequi', 'cash'
    public string $account_holder = '';
    public string $cedula = '';
    public string $phone = '';
    public string $account_number = '';
    public bool $state = true;
    public int $sort = 0;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:100',
            'currency_code' => 'required|string|in:VED,COP,USD,USDT',
            'type' => 'required|string|in:bank_transfer,pago_movil,card,zelle,binance,nequi,cash',
            'account_holder' => 'nullable|string|max:150',
            'cedula' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:50',
            'account_number' => 'nullable|string|max:100',
            'state' => 'boolean',
            'sort' => 'integer|min:0',
        ];
    }

    public function mount(): void
    {
        $config = Configuration::first();
        if ($config) {
            $this->bcvRate = (float) ($config->bcv_rate ?? 60.00);
            $this->copRate = (float) ($config->cop_rate ?? 4100.00);
            $this->binanceRate = (float) ($config->binance_rate ?? 1.00);
            $this->currencyMode = $config->currency_mode ?? 'multi';
        }
    }

    public function saveRates(): void
    {
        $this->validate([
            'bcvRate' => 'required|numeric|min:0.01',
            'copRate' => 'required|numeric|min:1',
            'binanceRate' => 'required|numeric|min:0.01',
        ]);

        $config = Configuration::first() ?? new Configuration();
        $config->bcv_rate = $this->bcvRate;
        $config->cop_rate = $this->copRate;
        $config->binance_rate = $this->binanceRate;
        $config->currency_mode = $this->currencyMode;
        $config->save();

        // Sincronizar también la tabla `currencies`
        Currency::where('code', 'VED')->update(['exchange_rate' => $this->bcvRate]);
        Currency::where('code', 'COP')->update(['exchange_rate' => $this->copRate]);
        Currency::where('code', 'USDT')->update(['exchange_rate' => $this->binanceRate]);
        Currency::where('code', 'USD')->update(['exchange_rate' => 1.0000]);

        $this->dispatch('notify', message: '✅ Tasas de cambio actualizadas correctamente', type: 'success');
    }

    public function openCreateModal(): void
    {
        $this->reset(['bankId', 'name', 'account_holder', 'cedula', 'phone', 'account_number']);
        $this->currency_code = 'VED';
        $this->type = 'pago_movil';
        $this->state = true;
        $this->sort = Bank::count() + 1;
        $this->showBankModal = true;
    }

    public function openEditModal(int $id): void
    {
        $bank = Bank::find($id);
        if (!$bank) return;

        $this->bankId = $bank->id;
        $this->name = $bank->name;
        $this->currency_code = strtoupper($bank->currency_code);
        $this->type = $bank->type;
        $this->account_holder = $bank->account_holder ?? '';
        $this->cedula = $bank->cedula ?? '';
        $this->phone = $bank->phone ?? '';
        $this->account_number = $bank->account_number ?? '';
        $this->state = (bool) $bank->state;
        $this->sort = (int) $bank->sort;
        $this->showBankModal = true;
    }

    public function saveBank(): void
    {
        $this->validate();

        $data = [
            'name' => trim($this->name),
            'currency_code' => strtoupper($this->currency_code),
            'type' => $this->type,
            'account_holder' => trim($this->account_holder) ?: null,
            'cedula' => trim($this->cedula) ?: null,
            'phone' => trim($this->phone) ?: null,
            'account_number' => trim($this->account_number) ?: null,
            'state' => $this->state,
            'sort' => $this->sort,
        ];

        if ($this->bankId) {
            $bank = Bank::find($this->bankId);
            if ($bank) {
                $bank->update($data);
                $this->dispatch('notify', message: "Cuenta '{$bank->name}' actualizada con éxito", type: 'success');
            }
        } else {
            Bank::create($data);
            $this->dispatch('notify', message: "Cuenta '{$this->name}' registrada con éxito", type: 'success');
        }

        $this->showBankModal = false;
        $this->reset(['bankId', 'name', 'account_holder', 'cedula', 'phone', 'account_number']);
    }

    public function toggleBankState(int $id): void
    {
        $bank = Bank::find($id);
        if (!$bank) return;

        $bank->state = !$bank->state;
        $bank->save();

        $statusStr = $bank->state ? 'activada' : 'desactivada';
        $this->dispatch('notify', message: "Cuenta {$bank->name} {$statusStr}", type: 'info');
    }

    public function deleteBank(int $id): void
    {
        $bank = Bank::find($id);
        if (!$bank) return;

        if ($bank->payments()->exists()) {
            $this->dispatch('notify', message: "No puedes eliminar '{$bank->name}' porque ya tiene pagos registrados. Puedes desactivarla en su lugar.", type: 'warning');
            return;
        }

        $bank->delete();
        $this->dispatch('notify', message: "Cuenta '{$bank->name}' eliminada exitosamente", type: 'success');
    }

    public function render()
    {
        $banks = Bank::orderBy('sort')->orderBy('name')->get();
        $currencies = Currency::all();

        return view('livewire.settings.bank-manager', [
            'banks' => $banks,
            'currencies' => $currencies,
        ]);
    }
}
