<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Area;
use App\Models\Table;

#[Layout('layouts.app')]
class AreaManager extends Component
{
    public ?int $selectedAreaId = null;

    // Modal Área
    public bool $showAreaModal = false;
    public ?int $areaId = null;
    public string $areaName = '';
    public string $areaDescription = '';
    public int $areaSortOrder = 0;
    public bool $areaIsActive = true;

    // Modal Mesa
    public bool $showTableModal = false;
    public ?int $tableId = null;
    public ?int $tableAreaId = null;
    public string $tableName = '';
    public int $tableCapacity = 4;
    public bool $tableIsActive = true;

    // Modal Creación de Mesas en Lote
    public bool $showBatchModal = false;
    public ?int $batchAreaId = null;
    public string $batchPrefix = 'Mesa ';
    public int $batchFrom = 1;
    public int $batchTo = 10;
    public int $batchCapacity = 4;

    public function mount(): void
    {
        $first = Area::orderBy('sort_order')->first();
        if ($first) {
            $this->selectedAreaId = $first->id;
        }
    }

    public function selectArea(?int $id): void
    {
        $this->selectedAreaId = $id;
    }

    // --- MÉTODOS DE ÁREAS ---
    public function openCreateAreaModal(): void
    {
        $this->reset(['areaId', 'areaName', 'areaDescription']);
        $this->areaSortOrder = Area::count() + 1;
        $this->areaIsActive = true;
        $this->showAreaModal = true;
    }

    public function openEditAreaModal(int $id): void
    {
        $area = Area::find($id);
        if (!$area) return;

        $this->areaId = $area->id;
        $this->areaName = $area->name;
        $this->areaDescription = $area->description ?? '';
        $this->areaSortOrder = (int) $area->sort_order;
        $this->areaIsActive = (bool) $area->is_active;
        $this->showAreaModal = true;
    }

    public function saveArea(): void
    {
        $this->validate([
            'areaName' => 'required|string|min:2|max:80',
            'areaDescription' => 'nullable|string|max:250',
            'areaSortOrder' => 'integer|min:0',
        ], [
            'areaName.required' => 'El nombre del área o salón es obligatorio.',
            'areaName.min' => 'El nombre debe tener al menos 2 caracteres.',
        ]);

        $data = [
            'name' => trim($this->areaName),
            'description' => trim($this->areaDescription) ?: null,
            'sort_order' => $this->areaSortOrder,
            'is_active' => $this->areaIsActive,
        ];

        if ($this->areaId) {
            $area = Area::find($this->areaId);
            if ($area) {
                $area->update($data);
                $this->dispatch('notify', message: "Área '{$area->name}' actualizada exitosamente", type: 'success');
            }
        } else {
            $newArea = Area::create($data);
            $this->selectedAreaId = $newArea->id;
            $this->dispatch('notify', message: "Nueva zona '{$newArea->name}' creada", type: 'success');
        }

        $this->showAreaModal = false;
        $this->reset(['areaId', 'areaName', 'areaDescription']);
    }

    public function toggleAreaActive(int $id): void
    {
        $area = Area::find($id);
        if (!$area) return;

        $area->is_active = !$area->is_active;
        $area->save();

        $statusStr = $area->is_active ? 'activada' : 'desactivada';
        $this->dispatch('notify', message: "Zona {$area->name} {$statusStr}", type: 'info');
    }

    public function deleteArea(int $id): void
    {
        $area = Area::with('tables.activeOrders')->find($id);
        if (!$area) return;

        // Validar si alguna mesa de esta zona tiene órdenes abiertas
        $hasActiveOrders = $area->tables->some(fn($t) => $t->activeOrders->isNotEmpty());
        if ($hasActiveOrders) {
            $this->dispatch('notify', message: "No puedes eliminar '{$area->name}' porque tiene mesas con cuentas activas en servicio.", type: 'error');
            return;
        }

        // Eliminar mesas asociadas y el área
        $area->tables()->delete();
        $area->delete();

        $this->selectedAreaId = Area::orderBy('sort_order')->value('id');
        $this->dispatch('notify', message: "Zona '{$area->name}' y sus mesas eliminadas", type: 'success');
    }

    // --- MÉTODOS DE MESAS ---
    public function openCreateTableModal(?int $areaId = null): void
    {
        $this->reset(['tableId', 'tableName']);
        $this->tableAreaId = $areaId ?: $this->selectedAreaId ?: Area::first()?->id;
        $this->tableCapacity = 4;
        $this->tableIsActive = true;

        $nextNum = Table::where('area_id', $this->tableAreaId)->count() + 1;
        $this->tableName = "Mesa {$nextNum}";

        $this->showTableModal = true;
    }

    public function openEditTableModal(int $id): void
    {
        $table = Table::find($id);
        if (!$table) return;

        $this->tableId = $table->id;
        $this->tableAreaId = $table->area_id;
        $this->tableName = $table->name;
        $this->tableCapacity = (int) $table->capacity;
        $this->tableIsActive = (bool) $table->is_active;
        $this->showTableModal = true;
    }

    public function saveTable(): void
    {
        $this->validate([
            'tableAreaId' => 'required|exists:areas,id',
            'tableName' => 'required|string|min:1|max:50',
            'tableCapacity' => 'required|integer|min:1|max:50',
        ], [
            'tableName.required' => 'El identificador o nombre de la mesa es obligatorio.',
            'tableCapacity.min' => 'La capacidad mínima es de 1 persona.',
        ]);

        $data = [
            'area_id' => $this->tableAreaId,
            'name' => trim($this->tableName),
            'capacity' => $this->tableCapacity,
            'is_active' => $this->tableIsActive,
        ];

        if ($this->tableId) {
            $table = Table::find($this->tableId);
            if ($table) {
                $table->update($data);
                $this->dispatch('notify', message: "Mesa '{$table->name}' actualizada", type: 'success');
            }
        } else {
            $data['status'] = 'free';
            $data['active_orders_count'] = 0;
            Table::create($data);
            $this->dispatch('notify', message: "Mesa '{$data['name']}' registrada", type: 'success');
        }

        $this->showTableModal = false;
        $this->reset(['tableId', 'tableName']);
    }

    public function toggleTableActive(int $id): void
    {
        $table = Table::find($id);
        if (!$table) return;

        if ($table->activeOrders()->exists()) {
            $this->dispatch('notify', message: "No puedes desactivar {$table->name} porque tiene cuentas activas en servicio.", type: 'warning');
            return;
        }

        $table->is_active = !$table->is_active;
        $table->save();

        $statusStr = $table->is_active ? 'habilitada' : 'inhabilitada';
        $this->dispatch('notify', message: "Mesa {$table->name} {$statusStr}", type: 'info');
    }

    public function deleteTable(int $id): void
    {
        $table = Table::find($id);
        if (!$table) return;

        if ($table->activeOrders()->exists()) {
            $this->dispatch('notify', message: "No puedes eliminar {$table->name} porque tiene cuentas abiertas actualmente.", type: 'error');
            return;
        }

        $name = $table->name;
        $table->delete();
        $this->dispatch('notify', message: "Mesa '{$name}' eliminada correctamente", type: 'success');
    }

    // --- CREACIÓN DE MESAS EN LOTE ---
    public function openBatchModal(int $areaId): void
    {
        $this->batchAreaId = $areaId;
        $this->batchPrefix = 'Mesa ';
        $this->batchFrom = Table::where('area_id', $areaId)->count() + 1;
        $this->batchTo = $this->batchFrom + 4;
        $this->batchCapacity = 4;
        $this->showBatchModal = true;
    }

    public function generateBatchTables(): void
    {
        $this->validate([
            'batchAreaId' => 'required|exists:areas,id',
            'batchPrefix' => 'required|string|max:30',
            'batchFrom' => 'required|integer|min:1|max:999',
            'batchTo' => 'required|integer|gte:batchFrom|max:999',
            'batchCapacity' => 'required|integer|min:1|max:50',
        ], [
            'batchTo.gte' => 'El número final debe ser mayor o igual al número inicial.',
        ]);

        $created = 0;
        for ($i = $this->batchFrom; $i <= $this->batchTo; $i++) {
            $name = trim($this->batchPrefix) . " {$i}";
            Table::firstOrCreate(
                ['area_id' => $this->batchAreaId, 'name' => $name],
                [
                    'capacity' => $this->batchCapacity,
                    'status' => 'free',
                    'active_orders_count' => 0,
                    'is_active' => true,
                ]
            );
            $created++;
        }

        $this->showBatchModal = false;
        $this->dispatch('notify', message: "Se han generado {$created} mesas en lote exitosamente", type: 'success');
    }

    public function render()
    {
        $areas = Area::withCount('tables')->orderBy('sort_order')->orderBy('name')->get();

        $activeArea = $this->selectedAreaId ? Area::with('tables')->find($this->selectedAreaId) : $areas->first();
        $tables = $activeArea ? $activeArea->tables : collect();

        return view('livewire.settings.area-manager', [
            'areas' => $areas,
            'activeArea' => $activeArea,
            'tables' => $tables,
        ]);
    }
}
