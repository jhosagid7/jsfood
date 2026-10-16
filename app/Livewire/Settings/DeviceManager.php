<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use App\Models\DeviceAuthorization;
use App\Models\Configuration;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class DeviceManager extends Component
{
    public string $deviceAccessMode = 'open';
    public int $maxDevices = 10;

    public function mount()
    {
        $config = Configuration::first();
        if ($config) {
            $this->deviceAccessMode = $config->device_access_mode ?? 'open';
            $this->maxDevices = $config->max_devices ?? 10;
        }
    }

    public function approveDevice(int $id)
    {
        $device = DeviceAuthorization::find($id);
        if ($device) {
            $device->update(['status' => 'approved']);
            $this->dispatch('notify', message: "Dispositivo aprobado exitosamente", type: 'success');
        }
    }

    public function rejectDevice(int $id)
    {
        $device = DeviceAuthorization::find($id);
        if ($device) {
            $device->update(['status' => 'rejected']);
            $this->dispatch('notify', message: "Dispositivo desaprobado / bloqueado", type: 'warning');
        }
    }

    public function deleteDevice(int $id)
    {
        $device = DeviceAuthorization::find($id);
        if ($device) {
            $device->delete();
            $this->dispatch('notify', message: "Dispositivo eliminado", type: 'info');
        }
    }

    public function savePolicy()
    {
        $config = Configuration::first();
        if ($config) {
            $config->update([
                'device_access_mode' => $this->deviceAccessMode,
                'max_devices' => $this->maxDevices,
            ]);
            $this->dispatch('notify', message: "Política de dispositivos actualizada", type: 'success');
        }
    }

    public function render()
    {
        $devices = DeviceAuthorization::orderBy('last_accessed_at', 'desc')->get();

        return view('livewire.settings.device-manager', [
            'devices' => $devices,
        ]);
    }
}
