<?php

namespace App\Livewire\Pos;

use Livewire\Component;
use App\Models\Table;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\Configuration;
use App\Services\TicketPrinterService;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class OrderTerminal extends Component
{
    public ?int $tableId = null;
    public ?int $orderId = null;

    // Filtros y Búsqueda
    public ?int $selectedCategoryId = null;
    public string $search = '';

    // Modificador / Nota de ítem modal
    public bool $showItemNoteModal = false;
    public ?int $editingItemId = null;
    public string $itemNoteText = '';

    public function mount($tableId = null)
    {
        $this->tableId = $tableId ? (int) $tableId : null;
        $orderIdParam = request()->query('order_id');

        if ($orderIdParam) {
            $this->orderId = (int) $orderIdParam;
        } elseif ($this->tableId) {
            // Cargar la última cuenta activa de la mesa o crear una
            $table = Table::find($this->tableId);
            if ($table) {
                $activeOrder = $table->activeOrders()->latest()->first();
                if ($activeOrder) {
                    $this->orderId = $activeOrder->id;
                } else {
                    $waiter = $this->resolveWaiter();
                    $newOrder = Order::create([
                        'table_id' => $table->id,
                        'area_id' => $table->area_id,
                        'user_id' => $waiter->id,
                        'account_label' => 'Cuenta Principal',
                        'service_type' => 'dine_in',
                    ]);
                    $this->orderId = $newOrder->id;
                    $table->syncStatus();
                }
            }
        } else {
            // Orden rápida de mostrador / para llevar
            $waiter = $this->resolveWaiter();
            $newOrder = Order::create([
                'user_id' => $waiter->id,
                'account_label' => 'Para Llevar / Mostrador',
                'service_type' => 'takeout',
            ]);
            $this->orderId = $newOrder->id;
        }

        // Seleccionar la primera categoría por defecto
        $firstCat = Category::where('is_active', true)->where('slug', '!=', 'insumos-cocina')->orderBy('sort_order')->first();
        if ($firstCat) {
            $this->selectedCategoryId = $firstCat->id;
        }
    }

    public function selectCategory(?int $catId)
    {
        $this->selectedCategoryId = $catId;
    }

    public function addProduct(int $productId)
    {
        if (!$this->orderId) return;

        $order = Order::find($this->orderId);
        $product = Product::find($productId);
        if (!$order || !$product) return;

        $existingItem = OrderItem::where('order_id', $order->id)
            ->where('product_id', $product->id)
            ->where('kitchen_status', 'pending')
            ->first();

        if ($existingItem) {
            $existingItem->increment('quantity');
            $existingItem->subtotal_usd = $existingItem->quantity * $existingItem->unit_price_usd;
            $existingItem->save();
        } else {
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price_usd' => $product->price_usd,
                'subtotal_usd' => $product->price_usd,
                'kitchen_status' => 'pending',
            ]);
        }

        $order->calculateTotals();
        $this->dispatch('notify', message: "+1 {$product->name}", type: 'info');
    }

    public function incrementItem(int $itemId)
    {
        $item = OrderItem::find($itemId);
        if ($item) {
            $item->increment('quantity');
            $item->subtotal_usd = $item->quantity * $item->unit_price_usd;
            $item->save();
            $item->order->calculateTotals();
        }
    }

    public function decrementItem(int $itemId)
    {
        $item = OrderItem::find($itemId);
        if ($item) {
            if ($item->quantity > 1) {
                $item->decrement('quantity');
                $item->subtotal_usd = $item->quantity * $item->unit_price_usd;
                $item->save();
            } else {
                $item->delete();
            }
            if ($item->order) {
                $item->order->calculateTotals();
            }
        }
    }

    public function openNoteModal(int $itemId)
    {
        $item = OrderItem::find($itemId);
        if ($item) {
            $this->editingItemId = $itemId;
            $this->itemNoteText = $item->notes ?? '';
            $this->showItemNoteModal = true;
        }
    }

    public function saveItemNote()
    {
        if ($this->editingItemId) {
            $item = OrderItem::find($this->editingItemId);
            if ($item) {
                $item->notes = trim($this->itemNoteText);
                $item->save();
            }
        }
        $this->showItemNoteModal = false;
        $this->reset(['editingItemId', 'itemNoteText']);
        $this->dispatch('notify', message: "Nota guardada para el plato", type: 'success');
    }

    public function sendOrderToKitchen()
    {
        $order = Order::with('items.product', 'diningTable')->find($this->orderId);
        if (!$order || $order->items->isEmpty()) {
            $this->dispatch('notify', message: "Agrega productos antes de enviar a cocina", type: 'warning');
            return;
        }

        $order->sendToKitchen();

        // Enviar a la impresora de cocina automáticamente
        $config = Configuration::first();
        $kitchenWidth = $config->kitchen_printer_width ?? 58;
        $escpos = TicketPrinterService::buildKitchenTicket($order, $kitchenWidth);
        TicketPrinterService::sendToPrinter($escpos, $config->kitchen_printer_name ?? 'KITCHEN-58');

        $this->dispatch('notify', message: "Comanda enviada a Cocina e Impresora Térmica 🍳", type: 'success');
    }

    public function printPreBill()
    {
        $order = Order::with('items.product', 'diningTable')->find($this->orderId);
        if (!$order || $order->items->isEmpty()) return;

        $config = Configuration::first();
        $width = $config->printer_width ?? 80;
        $escpos = TicketPrinterService::buildPreBillTicket($order, $width);
        TicketPrinterService::sendToPrinter($escpos, $config->printer_name ?? 'POS-80');

        $order->status = 'bill_requested';
        $order->save();
        if ($order->diningTable) {
            $order->diningTable->syncStatus();
        }

        $this->dispatch('notify', message: "Pre-cuenta impresa exitosamente 🧾", type: 'success');
    }

    protected function resolveWaiter(): \App\Models\User
    {
        if (auth()->check()) {
            return auth()->user();
        }

        return \App\Models\User::where('email', 'mesonero1@jsfood.dev')->first()
            ?? \App\Models\User::first()
            ?? \App\Models\User::create([
                'name' => 'Mesonero General',
                'email' => 'mesonero1@jsfood.dev',
                'password' => bcrypt('password123'),
                'role' => 'waiter',
            ]);
    }

    public function render()
    {
        $categories = Category::where('is_active', true)
            ->where('slug', '!=', 'insumos-cocina')
            ->orderBy('sort_order')
            ->get();

        $productsQuery = Product::where('is_active', true)
            ->where('type', '!=', 'supply');

        if ($this->selectedCategoryId) {
            $productsQuery->where('category_id', $this->selectedCategoryId);
        }

        if (!empty($this->search)) {
            $productsQuery->where('name', 'like', "%{$this->search}%");
        }

        $products = $productsQuery->orderBy('name')->get();

        $currentOrder = $this->orderId ? Order::with(['items.product', 'diningTable', 'customer'])->find($this->orderId) : null;

        return view('livewire.pos.order-terminal', [
            'categories' => $categories,
            'products' => $products,
            'currentOrder' => $currentOrder,
        ]);
    }
}
