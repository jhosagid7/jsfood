<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Models\User;
use App\Models\Area;
use App\Models\Table;
use App\Models\Order;
use App\Livewire\Settings\AreaManager;
use App\Livewire\Tables\TableMap;

class AreaManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'email' => 'admin_areas@jsfood.dev',
        ]);
    }

    public function test_area_manager_screen_renders_for_admin(): void
    {
        $this->actingAs($this->adminUser)
            ->get(route('settings.areas'))
            ->assertStatus(200)
            ->assertSee('Configuración de Áreas');
    }

    public function test_can_create_and_edit_area(): void
    {
        Livewire::actingAs($this->adminUser)
            ->test(AreaManager::class)
            ->call('openCreateAreaModal')
            ->set('areaName', 'Terraza VIP')
            ->set('areaDescription', 'Zona con vista panorámica')
            ->set('areaSortOrder', 5)
            ->call('saveArea')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('areas', [
            'name' => 'Terraza VIP',
            'description' => 'Zona con vista panorámica',
            'sort_order' => 5,
        ]);

        $area = Area::where('name', 'Terraza VIP')->first();

        // Editar
        Livewire::actingAs($this->adminUser)
            ->test(AreaManager::class)
            ->call('openEditAreaModal', $area->id)
            ->set('areaName', 'Terraza VIP Ejecutiva')
            ->call('saveArea');

        $this->assertDatabaseHas('areas', [
            'id' => $area->id,
            'name' => 'Terraza VIP Ejecutiva',
        ]);
    }

    public function test_can_create_edit_and_delete_table_in_area(): void
    {
        $area = Area::create([
            'name' => 'Barra Central',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        // Crear mesa
        Livewire::actingAs($this->adminUser)
            ->test(AreaManager::class)
            ->call('openCreateTableModal', $area->id)
            ->set('tableName', 'Barra 1')
            ->set('tableCapacity', 2)
            ->call('saveTable')
            ->assertDispatched('notify');

        $this->assertDatabaseHas('tables', [
            'area_id' => $area->id,
            'name' => 'Barra 1',
            'capacity' => 2,
        ]);

        $table = Table::where('name', 'Barra 1')->first();

        // Editar mesa
        Livewire::actingAs($this->adminUser)
            ->test(AreaManager::class)
            ->call('openEditTableModal', $table->id)
            ->set('tableCapacity', 3)
            ->call('saveTable');

        $this->assertDatabaseHas('tables', [
            'id' => $table->id,
            'capacity' => 3,
        ]);

        // Eliminar mesa
        Livewire::actingAs($this->adminUser)
            ->test(AreaManager::class)
            ->call('deleteTable', $table->id)
            ->assertDispatched('notify');

        $this->assertDatabaseMissing('tables', ['id' => $table->id]);
    }

    public function test_can_generate_batch_tables(): void
    {
        $area = Area::create([
            'name' => 'Patio Exterior',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(AreaManager::class)
            ->set('batchAreaId', $area->id)
            ->set('batchPrefix', 'Patio')
            ->set('batchFrom', 1)
            ->set('batchTo', 5)
            ->set('batchCapacity', 6)
            ->call('generateBatchTables')
            ->assertDispatched('notify');

        $this->assertEquals(5, Table::where('area_id', $area->id)->count());
        $this->assertDatabaseHas('tables', ['area_id' => $area->id, 'name' => 'Patio 1', 'capacity' => 6]);
        $this->assertDatabaseHas('tables', ['area_id' => $area->id, 'name' => 'Patio 5', 'capacity' => 6]);
    }

    public function test_cannot_delete_area_with_active_orders(): void
    {
        $area = Area::create(['name' => 'Salón Comedor']);
        $table = Table::create([
            'area_id' => $area->id,
            'name' => 'Mesa 10',
            'capacity' => 4,
            'status' => 'kitchen',
        ]);

        Order::create([
            'order_number' => 'ORD-AREA-TEST',
            'user_id' => $this->adminUser->id,
            'table_id' => $table->id,
            'area_id' => $area->id,
            'type' => 'dine_in',
            'status' => 'in_kitchen',
            'subtotal_usd' => 20.00,
            'total_usd' => 20.00,
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(AreaManager::class)
            ->call('deleteArea', $area->id)
            ->assertDispatched('notify');

        $this->assertDatabaseHas('areas', ['id' => $area->id]);
    }
}
