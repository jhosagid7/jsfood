<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use App\Models\Configuration;
use App\Services\PrinterDiscoveryService;
use App\Services\TicketPrinterService;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class PrinterSettings extends Component
{
    public string $cashierPrinter = 'POS-80';
    public int $cashierWidth = 80;

    public string $kitchenPrinter = 'KITCHEN-58';
    public int $kitchenWidth = 58;

    public string $currencyMode = 'multi';
    public float $bcvRate = 60.00;
    public float $copRate = 4100.00;
    public float $binanceRate = 62.00;

    public array $discoveredPrinters = [];
    public bool $isScanning = false;

    public function mount()
    {
        $config = Configuration::first();
        if ($config) {
            $this->cashierPrinter = $config->printer_name ?? 'POS-80';
            $this->cashierWidth = $config->printer_width ?? 80;
            $this->kitchenPrinter = $config->kitchen_printer_name ?? 'KITCHEN-58';
            $this->kitchenWidth = $config->kitchen_printer_width ?? 58;
            $this->currencyMode = $config->currency_mode ?? 'multi';
            $this->bcvRate = (float) $config->bcv_rate;
            $this->copRate = (float) $config->cop_rate;
            $this->binanceRate = (float) $config->binance_rate;
        }

        // Carga instantánea de impresoras locales (sin congelar la pantalla al abrir)
        $this->discoveredPrinters = PrinterDiscoveryService::getLocalPrinters();
    }

    public function scanPrinters()
    {
        $this->isScanning = true;
        $this->discoveredPrinters = PrinterDiscoveryService::discoverPrinters();
        $this->isScanning = false;
        $this->dispatch('notify', message: "Se detectaron " . count($this->discoveredPrinters) . " impresoras en la red", type: 'info');
    }

    public function selectPrinterForCashier(int $index)
    {
        if (isset($this->discoveredPrinters[$index])) {
            $p = $this->discoveredPrinters[$index];
            $name = $this->normalizePrinterName($p['unc'] ?? $p['name'] ?? '');
            $this->cashierPrinter = $name;
            Configuration::query()->update(['printer_name' => $name]);
            $this->dispatch('notify', message: "Impresora de caja asignada y guardada: {$name}", type: 'success');
        }
    }

    public function selectPrinterForKitchen(int $index)
    {
        if (isset($this->discoveredPrinters[$index])) {
            $p = $this->discoveredPrinters[$index];
            $name = $this->normalizePrinterName($p['unc'] ?? $p['name'] ?? '');
            $this->kitchenPrinter = $name;
            Configuration::query()->update(['kitchen_printer_name' => $name]);
            $this->dispatch('notify', message: "Impresora de cocina asignada y guardada: {$name}", type: 'success');
        }
    }

    protected function normalizePrinterName(string $printer): string
    {
        $printer = trim($printer);
        // Si detecta un patrón IP o nombre de red con barras faltantes o invertidas
        if (preg_match('/^\\\\*([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})(?:\\\\|\/)?(.+)$/', $printer, $matches)) {
            return "\\\\{$matches[1]}\\" . ltrim($matches[2], '\\/');
        }

        return $printer;
    }

    public function saveSettings()
    {
        $this->cashierPrinter = $this->normalizePrinterName($this->cashierPrinter);
        $this->kitchenPrinter = $this->normalizePrinterName($this->kitchenPrinter);

        $config = Configuration::first();
        if ($config) {
            $config->update([
                'printer_name' => $this->cashierPrinter,
                'printer_width' => $this->cashierWidth,
                'kitchen_printer_name' => $this->kitchenPrinter,
                'kitchen_printer_width' => $this->kitchenWidth,
                'currency_mode' => $this->currencyMode,
                'bcv_rate' => $this->bcvRate,
                'cop_rate' => $this->copRate,
                'binance_rate' => $this->binanceRate,
            ]);
        }

        $this->dispatch('notify', message: "Configuración de hardware y monedas guardada con éxito", type: 'success');
    }

    public function testPrint(string $type = 'cashier')
    {
        $printerName = ($type === 'kitchen') ? $this->kitchenPrinter : $this->cashierPrinter;
        $printerName = $this->normalizePrinterName($printerName);

        if ($type === 'kitchen') {
            $this->kitchenPrinter = $printerName;
        } else {
            $this->cashierPrinter = $printerName;
        }

        $width = ($type === 'kitchen') ? $this->kitchenWidth : $this->cashierWidth;

        $w = TicketPrinterService::getColumnWidth($width);
        $testData = TicketPrinterService::INIT_PRINTER;
        $testData .= TicketPrinterService::ALIGN_CENTER . TicketPrinterService::BOLD_ON . TicketPrinterService::DOUBLE_HEIGHT_ON;
        $testData .= "JSFOOD POS - TEST\n";
        $testData .= TicketPrinterService::NORMAL_TEXT . TicketPrinterService::BOLD_OFF;
        $testData .= "Prueba de Impresion ({$width}mm)\n";
        $testData .= TicketPrinterService::separator($w, '=');
        $testData .= TicketPrinterService::formatTwoColumns("COLUMNA 1", "COLUMNA 2", $w);
        $testData .= TicketPrinterService::formatTwoColumns("Prueba OK", "$99.99", $w);
        $testData .= TicketPrinterService::separator($w, '-');
        $testData .= "Impresora: {$printerName}\n";
        $testData .= "Fecha: " . now()->format('d/m/Y h:i A') . "\n\n\n";
        $testData .= TicketPrinterService::FEED_AND_CUT;

        $success = TicketPrinterService::sendToPrinter($testData, $printerName);
        if ($success) {
            if ($type === 'kitchen') {
                Configuration::query()->update(['kitchen_printer_name' => $printerName, 'kitchen_printer_width' => $width]);
            } else {
                Configuration::query()->update(['printer_name' => $printerName, 'printer_width' => $width]);
            }
            $this->dispatch('notify', message: "Ticket impreso y configuración guardada en {$printerName}", type: 'success');
        } else {
            $this->dispatch('notify', message: "Error al enviar prueba a {$printerName}. Verifica la conexión de red.", type: 'error');
        }
    }

    public function render()
    {
        return view('livewire.settings.printer-settings');
    }
}
