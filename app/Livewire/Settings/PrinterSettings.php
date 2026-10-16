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

        $this->scanPrinters();
    }

    public function scanPrinters()
    {
        $this->isScanning = true;
        $this->discoveredPrinters = PrinterDiscoveryService::discoverPrinters();
        $this->isScanning = false;
        $this->dispatch('notify', message: "Se detectaron " . count($this->discoveredPrinters) . " impresoras", type: 'info');
    }

    public function saveSettings()
    {
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
            $this->dispatch('notify', message: "Ticket de prueba enviado a {$printerName}", type: 'success');
        } else {
            $this->dispatch('notify', message: "Error al enviar prueba a {$printerName}", type: 'error');
        }
    }

    public function render()
    {
        return view('livewire.settings.printer-settings');
    }
}
