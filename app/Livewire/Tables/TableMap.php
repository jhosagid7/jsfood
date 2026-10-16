<?php

namespace App\Livewire\Tables;

use Livewire\Component;
use App\Models\Area;
use App\Models\Table;
use App\Models\Order;
use App\Models\Customer;
use App\Services\TicketPrinterService;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class TableMap extends Component
{
    public ?int $selectedAreaId = null;
    public ?int $selectedTableId = null;

    // Modal states
    public bool $showTableModal = false;
    public bool $showNewAccountModal = false;

    // Nueva orden / sub-cuenta
    public string $newAccountLabel = '';
    public ?int $newCustomerId = null;
    public int $guestsCount = 2;

    public function mount()
    {
        $firstArea = Area::orderBy('sort_order')->first();
        if ($firstArea) {
            $this->selectedAreaId = $firstArea->id;
        }
    }

    public function selectArea(?int $areaId)
    {
        $this->selectedAreaId = $areaId;
    }

    public function openTable(int $tableId)
    {
        $this->selectedTableId = $tableId;
        $table = Table::with('activeOrders.items', 'activeOrders.customer')->find($tableId);

        if ($table && $table->activeOrders->isEmpty()) {
            // Si la mesa está libre, abrir directamente el diálogo para crear la primera cuenta
            $this->newAccountLabel = 'Cuenta Principal';
            $this->showNewAccountModal = true;
            $this->showTableModal = false;
        } else {
            // Mostrar lista de cuentas activas en la mesa
            $this->showTableModal = true;
            $this->showNewAccountModal = false;
        }
    }

    public function createNewAccount()
    {
        if (!$this->selectedTableId) return;

        $table = Table::find($this->selectedTableId);
        if (!$table) return;

        $waiter = auth()->user() ?? \App\Models\User::where('email', 'mesonero1@jsfood.dev')->first() ?? \App\Models\User::first();

        $accountLabel = trim($this->newAccountLabel);
        if (empty($accountLabel)) {
            $nextNum = $table->activeOrders()->count() + 1;
            $accountLabel = "Cuenta {$nextNum}";
        }

        $order = Order::create([
            'table_id' => $table->id,
            'area_id' => $table->area_id,
            'user_id' => $waiter->id,
            'customer_id' => $this->newCustomerId,
            'service_type' => 'dine_in',
            'account_label' => $accountLabel,
            'status' => 'pending',
        ]);

        $table->syncStatus();

        $this->showNewAccountModal = false;
        $this->showTableModal = false;
        $this->reset(['newAccountLabel', 'newCustomerId']);

        $this->dispatch('notify', message: "Cuenta '{$accountLabel}' abierta en {$table->name}", type: 'success');

        return redirect()->to("/pos/order/{$table->id}?order_id={$order->id}");
    }

    public function printPreBill(int $orderId)
    {
        $order = Order::with('items.product', 'diningTable')->find($orderId);
        if (!$order) return;

        $config = \App\Models\Configuration::first();
        $width = $config->printer_width ?? 80;
        $escpos = TicketPrinterService::buildPreBillTicket($order, $width);
        TicketPrinterService::sendToPrinter($escpos, $config->printer_name ?? 'POS-80');

        $order->status = 'bill_requested';
        $order->save();
        if ($order->diningTable) {
            $order->diningTable->syncStatus();
        }

        $this->dispatch('notify', message: "Pre-cuenta de {$order->account_label} enviada a imprimir", type: 'success');
    }

    public function render()
    {
        $areas = Area::where('is_active', true)->orderBy('sort_order')->get();

        $tablesQuery = Table::query()
            ->with(['activeOrders.items', 'activeOrders.customer'])
            ->where('is_active', true);

        if ($this->selectedAreaId) {
            $tablesQuery->where('area_id', $this->selectedAreaId);
        }

        $tables = $tablesQuery->get();
        $selectedTable = $this->selectedTableId ? Table::with('activeOrders.items.product', 'activeOrders.customer')->find($this->selectedTableId) : null;
        $customers = Customer::orderBy('name')->get();

        return view('livewire.tables.table-map', [
            'areas' => $areas,
            'tables' => $tables,
            'selectedTable' => $selectedTable,
            'customers' => $customers,
        ]);
    }
}
