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
            $this->dispatch('notify', message: "¡Orden #{$order->order_number} lista para servir!", type: 'success');
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
        TicketPrinterService::sendToPrinter($escpos, $config->kitchen_printer_name ?? 'KITCHEN-58');

        $this->dispatch('notify', message: "Comanda reimpresa en tiquera de cocina", type: 'success');
    }

    public function render()
    {
        $query = Order::with(['items.product', 'diningTable', 'user'])
            ->whereNotIn('status', ['paid', 'cancelled']);

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
