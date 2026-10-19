<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\DeviceAuthorization;
use App\Livewire\Settings\DeviceManager;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DeviceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RoleSeeder::class);
        $this->seed(\Database\Seeders\UserSeeder::class);
        $this->seed(\Database\Seeders\RestaurantSampleSeeder::class);
    }

    public function test_approved_device_can_access_system()
    {
        $user = User::first();
        $this->actingAs($user);

        $device = DeviceAuthorization::create([
            'uuid' => 'test-device-approved-uuid',
            'name' => 'Dispositivo Aprobado',
            'ip_address' => '192.168.20.200',
            'user_agent' => 'TestBrowser/1.0',
            'status' => 'approved',
            'last_accessed_at' => now(),
        ]);

        $response = $this->withCookie('device_token', $device->uuid)
            ->get('/tables');

        $response->assertStatus(200);
    }

    public function test_rejected_device_is_blocked_even_for_super_admin()
    {
        $superAdmin = User::where('email', 'admin@jsfood.dev')->first() ?? User::first();
        $this->actingAs($superAdmin);

        $device = DeviceAuthorization::create([
            'uuid' => 'test-device-rejected-uuid',
            'name' => 'Dispositivo Bloqueado',
            'ip_address' => '192.168.20.201',
            'user_agent' => 'TestBrowser/1.0',
            'status' => 'rejected',
            'last_accessed_at' => now(),
        ]);

        $response = $this->withCookie('device_token', $device->uuid)
            ->get('/tables');

        // Debe ser redirigido a access-denied
        $response->assertRedirect(route('access.denied', ['device_uuid' => $device->uuid]));
    }

    public function test_rejected_device_matched_by_ip_fingerprint_is_blocked()
    {
        $user = User::first();
        $this->actingAs($user);

        $device = DeviceAuthorization::create([
            'uuid' => 'test-device-fingerprint-uuid',
            'name' => 'Dispositivo Bloqueado IP',
            'ip_address' => '192.168.20.202',
            'user_agent' => 'FingerprintUA/1.0',
            'status' => 'rejected',
            'last_accessed_at' => now(),
        ]);

        // Petición sin cookie, pero con la misma IP y User-Agent
        $response = $this->withServerVariables([
            'REMOTE_ADDR' => '192.168.20.202',
            'HTTP_USER_AGENT' => 'FingerprintUA/1.0',
        ])->get('/tables');

        $response->assertRedirect(route('access.denied', ['device_uuid' => $device->uuid]));
    }

    public function test_device_manager_can_reject_and_approve_devices()
    {
        $user = User::first();
        $this->actingAs($user);

        $device = DeviceAuthorization::create([
            'uuid' => 'test-device-toggle-uuid',
            'name' => 'Dispositivo Toggle',
            'ip_address' => '192.168.20.203',
            'user_agent' => 'ToggleUA/1.0',
            'status' => 'approved',
            'last_accessed_at' => now(),
        ]);

        // Rechazar
        Livewire::test(DeviceManager::class)
            ->call('rejectDevice', $device->id)
            ->assertDispatched('notify');

        $this->assertEquals('rejected', $device->fresh()->status);

        // Volver a aprobar
        Livewire::test(DeviceManager::class)
            ->call('approveDevice', $device->id)
            ->assertDispatched('notify');

        $this->assertEquals('approved', $device->fresh()->status);
    }
}
