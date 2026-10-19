<?php

namespace App\Livewire\Kitchen;

use Livewire\Component;
use App\Models\Order;
use App\Models\Configuration;
use App\Services\TicketPrinterService;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class KitchenDisplay extends Component
{
    public string $filter = 'active'; // 'active', 'in_kitchen', 'ready'
    public array $knownOrderIds = [];

    public function mount()
    {
        $this->knownOrderIds = Order::whereIn('status', ['pending', 'in_kitchen'])
            ->whereHas('items.product', fn($q) => $q->where('send_to_kitchen', true))
            ->pluck('id')->toArray();
    }

    public function checkNewOrders()
    {
        $currentActiveIds = Order::whereIn('status', ['pending', 'in_kitchen'])
            ->whereHas('items.product', fn($q) => $q->where('send_to_kitchen', true))
            ->pluck('id')->toArray();
        $newIds = array_diff($currentActiveIds, $this->knownOrderIds);

        if (!empty($newIds)) {
            $this->dispatch('play-kitchen-chime');
            $count = count($newIds);
            $this->dispatch('notify', message: "🔔 ¡Nueva comanda recibida en cocina ({$count})!", type: 'warning');
            $this->knownOrderIds = $currentActiveIds;
        } else {
            $this->knownOrderIds = $currentActiveIds;
        }
    }

    public function markAsPreparing(int $orderId)
    {
        $order = Order::with('diningTable')->find($orderId);
        if ($order) {
            $order->sendToKitchen();
            $this->dispatch('notify', message: "Orden #{$order->order_number} en preparación", type: 'info');
        }
    }

    public function markAsReady(int $orderId)
    {
        $order = Order::with('diningTable')->find($orderId);
        if ($order) {
            $order->markReady();
            $this->dispatch('play-waiter-chime');
            $this->dispatch('notify', message: "¡Orden #{$order->order_number} lista para servir! Mesonero notificado.", type: 'success');
        }
    }

    public function markAsServed(int $orderId)
    {
        $order = Order::with('diningTable')->find($orderId);
        if ($order) {
            $order->markServed();
            $this->dispatch('notify', message: "Orden #{$order->order_number} entregada al cliente", type: 'info');
        }
    }

    public function reprintKitchenTicket(int $orderId)
    {
        $order = Order::with('items.product', 'diningTable')->find($orderId);
        if (!$order) return;

        $config = Configuration::first();
        $kitchenWidth = $config->kitchen_printer_width ?? 58;
        $escpos = TicketPrinterService::buildKitchenTicket($order, $kitchenWidth);
        if (!empty($escpos)) {
            TicketPrinterService::sendToPrinter($escpos, $config->kitchen_printer_name ?? 'KITCHEN-58');
            $this->dispatch('notify', message: "Comanda reimpresa en tiquera de cocina", type: 'success');
        } else {
            $this->dispatch('notify', message: "La orden no contiene ítems para cocina", type: 'warning');
        }
    }

    public function render()
    {
        $query = Order::with(['items.product', 'diningTable', 'user'])
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->whereHas('items.product', fn($q) => $q->where('send_to_kitchen', true));

        if ($this->filter === 'in_kitchen') {
            $query->where('status', 'in_kitchen');
        } elseif ($this->filter === 'ready') {
            $query->where('status', 'ready');
        } else {
            // 'active': muestra las que están pendientes, en cocina y listas
            $query->whereIn('status', ['pending', 'in_kitchen', 'ready']);
        }

        $orders = $query->orderBy('kitchen_sent_at', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        return view('livewire.kitchen.kitchen-display', [
            'orders' => $orders,
        ]);
    }
}
