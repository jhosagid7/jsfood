<?php

namespace App\Livewire\Products;

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use App\Models\Product;
use App\Models\Category;
use App\Models\Configuration;
use App\Models\ProductRecipe;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class ProductList extends Component
{
    use WithFileUploads;
    use WithPagination;

    public bool $showModal = false;
    public ?int $editingProductId = null;

    // Form fields
    public string $name = '';
    public ?string $code = null;
    public ?int $categoryId = null;
    public ?string $description = null;
    public float $priceUsd = 0.00;
    public string $type = 'dish'; // 'dish', 'direct', 'supply'
    public bool $sendToKitchen = true;
    public bool $hasRecipe = false;
    public bool $trackStock = true;
    public float $currentStock = 0.00;
    public float $minStock = 0.00;
    public string $unit = 'unit'; // 'unit', 'kg', 'portion', 'liter'
    public bool $isActive = true;

    // Image upload
    public $photo = null;
    public ?string $existingImage = null;

    // Recipe items: [ ['supply_id' => ..., 'quantity_needed' => ..., 'unit' => ...] ]
    public array $recipeItems = [];

    // Filters
    public string $search = '';
    public string $filterCategory = '';
    public string $filterType = '';
    public string $filterSendToKitchen = '';
    public bool $filterLowStock = false;

    protected function rules(): array
    {
        return [
            'name' => 'required|min:2|max:150',
            'code' => 'nullable|string|max:50',
            'categoryId' => 'nullable|exists:categories,id',
            'description' => 'nullable|string|max:500',
            'priceUsd' => 'required|numeric|min:0',
            'type' => 'required|in:dish,direct,supply',
            'sendToKitchen' => 'boolean',
            'hasRecipe' => 'boolean',
            'trackStock' => 'boolean',
            'currentStock' => 'required|numeric|min:0',
            'minStock' => 'required|numeric|min:0',
            'unit' => 'required|string|max:20',
            'isActive' => 'boolean',
            'photo' => 'nullable|image|max:2048',
            'recipeItems.*.supply_id' => 'required_if:hasRecipe,true|exists:products,id',
            'recipeItems.*.quantity_needed' => 'required_if:hasRecipe,true|numeric|min:0.001',
            'recipeItems.*.unit' => 'nullable|string',
        ];
    }

    public function updatedType($value): void
    {
        if ($value === 'supply') {
            $this->hasRecipe = false;
            $this->trackStock = true;
            $this->sendToKitchen = false;
        } elseif ($value === 'direct') {
            $this->hasRecipe = false;
            $this->trackStock = true;
            $this->sendToKitchen = false;
        } elseif ($value === 'dish') {
            $this->sendToKitchen = true;
            if (empty($this->recipeItems) && $this->hasRecipe) {
                $this->addRecipeItem();
            }
        }
    }

    public function updatedHasRecipe($value): void
    {
        if ($value && empty($this->recipeItems)) {
            $this->addRecipeItem();
        }
    }

    public function openCreateModal(): void
    {
        $this->reset([
            'editingProductId', 'name', 'code', 'categoryId', 'description', 
            'priceUsd', 'type', 'sendToKitchen', 'hasRecipe', 'trackStock', 'currentStock', 
            'minStock', 'unit', 'isActive', 'photo', 'existingImage', 'recipeItems'
        ]);

        $this->type = 'dish';
        $this->sendToKitchen = true;
        $this->unit = 'unit';
        $this->trackStock = true;
        $this->isActive = true;
        $this->priceUsd = 0.00;
        $this->showModal = true;
    }

    public function editProduct(int $id): void
    {
        $p = Product::with('recipeItems.supply')->findOrFail($id);
        $this->editingProductId = $p->id;
        $this->name = $p->name;
        $this->code = $p->code;
        $this->categoryId = $p->category_id;
        $this->description = $p->description;
        $this->priceUsd = (float) $p->price_usd;
        $this->type = $p->type;
        $this->sendToKitchen = (bool) ($p->send_to_kitchen ?? true);
        $this->hasRecipe = (bool) $p->has_recipe;
        $this->trackStock = (bool) $p->track_stock;
        $this->currentStock = (float) $p->current_stock;
        $this->minStock = (float) $p->min_stock;
        $this->unit = $p->unit;
        $this->isActive = (bool) $p->is_active;
        $this->existingImage = $p->image;
        $this->photo = null;

        $this->recipeItems = $p->recipeItems->map(fn($item) => [
            'id' => $item->id,
            'supply_id' => $item->supply_id,
            'quantity_needed' => (float) $item->quantity_needed,
            'unit' => $item->unit ?? 'unit',
        ])->toArray();

        $this->showModal = true;
    }

    public function removeImage(): void
    {
        if ($this->editingProductId && $this->existingImage) {
            if (Storage::disk('public')->exists($this->existingImage)) {
                Storage::disk('public')->delete($this->existingImage);
            }
            Product::where('id', $this->editingProductId)->update(['image' => null]);
        }
        $this->photo = null;
        $this->existingImage = null;
    }

    public function addRecipeItem(): void
    {
        $firstSupply = Product::where('type', 'supply')->where('is_active', true)->first();
        $this->recipeItems[] = [
            'supply_id' => $firstSupply?->id,
            'quantity_needed' => 1.0,
            'unit' => $firstSupply?->unit ?? 'unit',
        ];
    }

    public function removeRecipeItem(int $index): void
    {
        unset($this->recipeItems[$index]);
        $this->recipeItems = array_values($this->recipeItems);
    }

    public function saveProduct(): void
    {
        $this->validate();

        $imagePath = $this->existingImage;
        if ($this->photo) {
            $imagePath = $this->photo->store('products', 'public');
        }

        $data = [
            'name' => trim($this->name),
            'code' => $this->code ? trim($this->code) : null,
            'category_id' => $this->categoryId,
            'description' => $this->description ? trim($this->description) : null,
            'price_usd' => $this->priceUsd,
            'type' => $this->type,
            'send_to_kitchen' => $this->sendToKitchen,
            'has_recipe' => $this->type === 'dish' ? $this->hasRecipe : false,
            'track_stock' => $this->trackStock,
            'current_stock' => $this->currentStock,
            'min_stock' => $this->minStock,
            'unit' => $this->unit,
            'is_active' => $this->isActive,
            'image' => $imagePath,
        ];

        if ($this->editingProductId) {
            $product = Product::findOrFail($this->editingProductId);
            $product->update($data);

            // Sincronizar Ficha Técnica si es plato con receta
            if ($product->has_recipe) {
                $product->recipeItems()->delete();
                foreach ($this->recipeItems as $item) {
                    if (!empty($item['supply_id']) && (float)($item['quantity_needed'] ?? 0) > 0) {
                        $product->recipeItems()->create([
                            'supply_id' => $item['supply_id'],
                            'quantity_needed' => (float) $item['quantity_needed'],
                            'unit' => $item['unit'] ?? 'unit',
                        ]);
                    }
                }
            } else {
                $product->recipeItems()->delete();
            }

            $this->dispatch('notify', message: "Producto '{$product->name}' actualizado con éxito", type: 'success');
        } else {
            $product = Product::create($data);

            // Guardar Ficha Técnica si es plato con receta
            if ($product->has_recipe) {
                foreach ($this->recipeItems as $item) {
                    if (!empty($item['supply_id']) && (float)($item['quantity_needed'] ?? 0) > 0) {
                        $product->recipeItems()->create([
                            'supply_id' => $item['supply_id'],
                            'quantity_needed' => (float) $item['quantity_needed'],
                            'unit' => $item['unit'] ?? 'unit',
                        ]);
                    }
                }
            }

            $this->dispatch('notify', message: "Producto '{$product->name}' registrado con éxito", type: 'success');
        }

        $this->showModal = false;
        $this->reset([
            'editingProductId', 'name', 'code', 'categoryId', 'description', 
            'priceUsd', 'type', 'sendToKitchen', 'hasRecipe', 'trackStock', 'currentStock', 
            'minStock', 'unit', 'isActive', 'photo', 'existingImage', 'recipeItems'
        ]);
    }

    public function toggleStatus(int $id): void
    {
        $p = Product::findOrFail($id);
        $p->update(['is_active' => !$p->is_active]);
        $statusText = $p->is_active ? 'activado' : 'desactivado';
        $this->dispatch('notify', message: "Producto '{$p->name}' {$statusText}", type: 'info');
    }

    public function toggleSendToKitchen(int $id): void
    {
        $p = Product::findOrFail($id);
        $p->update(['send_to_kitchen' => !$p->send_to_kitchen]);
        $dest = $p->send_to_kitchen ? 'enviará a Cocina 🍳' : 'entregará de forma Directa (sin Cocina) 🥤';
        $this->dispatch('notify', message: "Producto '{$p->name}' ahora se {$dest}", type: 'info');
    }

    public function deleteProduct(int $id): void
    {
        $p = Product::findOrFail($id);

        // Verificar si se usa como insumo en recetas de otros platos
        if ($p->usedInRecipes()->exists()) {
            $this->dispatch('notify', message: "No se puede eliminar '{$p->name}' porque se utiliza como insumo en la receta de otros platos.", type: 'warning');
            return;
        }

        $name = $p->name;
        if ($p->image && Storage::disk('public')->exists($p->image)) {
            Storage::disk('public')->delete($p->image);
        }

        $p->recipeItems()->delete();
        $p->delete();

        $this->dispatch('notify', message: "Producto '{$name}' eliminado del catálogo", type: 'info');
    }

    public function render()
    {
        $categories = Category::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $supplies = Product::where('type', 'supply')->where('is_active', true)->orderBy('name')->get();
        $config = Configuration::first();

        $products = Product::with(['category', 'recipeItems.supply'])
            ->when($this->search, function ($q) {
                $term = '%' . trim($this->search) . '%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', $term)
                        ->orWhere('code', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            })
            ->when($this->filterCategory, fn($q) => $q->where('category_id', $this->filterCategory))
            ->when($this->filterType, fn($q) => $q->where('type', $this->filterType))
            ->when($this->filterSendToKitchen !== '', fn($q) => $q->where('send_to_kitchen', $this->filterSendToKitchen === '1'))
            ->when($this->filterLowStock, fn($q) => $q->where('track_stock', true)->whereColumn('current_stock', '<=', 'min_stock'))
            ->orderBy('category_id')
            ->orderBy('name')
            ->paginate(12);

        return view('livewire.products.product-list', [
            'products' => $products,
            'categories' => $categories,
            'supplies' => $supplies,
            'config' => $config,
        ]);
    }
}
