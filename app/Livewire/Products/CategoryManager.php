<?php

namespace App\Livewire\Products;

use Livewire\Component;
use App\Models\Category;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class CategoryManager extends Component
{
    public bool $showModal = false;
    public ?int $editingCategoryId = null;

    public string $name = '';
    public string $icon = '🍔';
    public string $color = 'amber';
    public int $sortOrder = 0;
    public bool $isActive = true;

    public string $search = '';

    protected function rules(): array
    {
        return [
            'name' => 'required|min:2|max:100',
            'icon' => 'nullable|string|max:50',
            'color' => 'required|string|max:30',
            'sortOrder' => 'required|integer|min:0',
            'isActive' => 'boolean',
        ];
    }

    public function openCreateModal(): void
    {
        $this->reset(['name', 'icon', 'color', 'sortOrder', 'isActive', 'editingCategoryId']);
        $this->icon = '🍔';
        $this->color = 'amber';
        $this->sortOrder = Category::max('sort_order') + 1;
        $this->isActive = true;
        $this->showModal = true;
    }

    public function editCategory(int $id): void
    {
        $cat = Category::findOrFail($id);
        $this->editingCategoryId = $cat->id;
        $this->name = $cat->name;
        $this->icon = $cat->icon ?? '🍔';
        $this->color = $cat->color ?? 'amber';
        $this->sortOrder = $cat->sort_order;
        $this->isActive = (bool) $cat->is_active;
        $this->showModal = true;
    }

    public function saveCategory(): void
    {
        $this->validate();

        $slugBase = Str::slug($this->name);
        $slug = $slugBase;
        $counter = 1;

        while (Category::where('slug', $slug)
            ->when($this->editingCategoryId, fn($q) => $q->where('id', '!=', $this->editingCategoryId))
            ->exists()) {
            $slug = "{$slugBase}-{$counter}";
            $counter++;
        }

        if ($this->editingCategoryId) {
            $category = Category::findOrFail($this->editingCategoryId);
            $category->update([
                'name' => trim($this->name),
                'slug' => $slug,
                'icon' => trim($this->icon) ?: '🍔',
                'color' => $this->color,
                'sort_order' => $this->sortOrder,
                'is_active' => $this->isActive,
            ]);
            $this->dispatch('notify', message: "Categoría '{$category->name}' actualizada con éxito", type: 'success');
        } else {
            $category = Category::create([
                'name' => trim($this->name),
                'slug' => $slug,
                'icon' => trim($this->icon) ?: '🍔',
                'color' => $this->color,
                'sort_order' => $this->sortOrder,
                'is_active' => $this->isActive,
            ]);
            $this->dispatch('notify', message: "Categoría '{$category->name}' creada con éxito", type: 'success');
        }

        $this->showModal = false;
        $this->reset(['name', 'icon', 'color', 'sortOrder', 'isActive', 'editingCategoryId']);
    }

    public function toggleStatus(int $id): void
    {
        $cat = Category::findOrFail($id);
        $cat->update(['is_active' => !$cat->is_active]);
        $statusText = $cat->is_active ? 'activada' : 'desactivada';
        $this->dispatch('notify', message: "Categoría '{$cat->name}' {$statusText}", type: 'info');
    }

    public function deleteCategory(int $id): void
    {
        $cat = Category::withCount('products')->findOrFail($id);

        if ($cat->products_count > 0) {
            $this->dispatch('notify', message: "No se puede eliminar la categoría '{$cat->name}' porque tiene {$cat->products_count} producto(s) asignado(s). Reasigna los productos primero.", type: 'warning');
            return;
        }

        $name = $cat->name;
        $cat->delete();
        $this->dispatch('notify', message: "Categoría '{$name}' eliminada correctamente", type: 'info');
    }

    public function render()
    {
        $categories = Category::withCount('products')
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . trim($this->search) . '%');
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('livewire.products.category-manager', [
            'categories' => $categories,
        ]);
    }
}
