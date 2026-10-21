<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Configuration;
use App\Models\DeviceAuthorization;
use App\Helpers\CurrencyHelper;
use Illuminate\Support\Facades\Log;

class TicketPrinterService
{
    const ESC = "\x1b";
    const GS  = "\x1d";

    const INIT_PRINTER     = "\x1b\x40";
    const ALIGN_LEFT       = "\x1b\x61\x00";
    const ALIGN_CENTER     = "\x1b\x61\x01";
    const ALIGN_RIGHT      = "\x1b\x61\x02";
    const BOLD_ON          = "\x1b\x45\x01";
    const BOLD_OFF         = "\x1b\x45\x00";
    const DOUBLE_HEIGHT_ON = "\x1b\x21\x10";
    const DOUBLE_WIDTH_ON  = "\x1b\x21\x20";
    const DOUBLE_BOTH_ON   = "\x1b\x21\x30";
    const NORMAL_TEXT      = "\x1b\x21\x00";
    const FEED_AND_CUT     = "\x1d\x56\x41\x03"; // Full cut
    const OPEN_DRAWER      = "\x1b\x70\x00\x19\xfa"; // Apertura de gaveta

    /**
     * Resuelve el ancho de columnas (32 cols para 58mm, 48 cols para 80mm).
     */
    public static function getColumnWidth(int $widthMm = 80): int
    {
        return ($widthMm === 58) ? 32 : 48;
    }

    /**
     * Justifica dos columnas (ej. Descripción a la izquierda y Total a la derecha).
     */
    public static function formatTwoColumns(string $left, string $right, int $maxWidth): string
    {
        $rightLen = mb_strlen($right);
        $maxLeftLen = max(1, $maxWidth - $rightLen - 1);

        if (mb_strlen($left) > $maxLeftLen) {
            $left = mb_substr($left, 0, $maxLeftLen);
        }

        $spaces = $maxWidth - mb_strlen($left) - $rightLen;
        return $left . str_repeat(' ', max(1, $spaces)) . $right . "\n";
    }

    /**
     * Línea divisoria adaptable al ancho.
     */
    public static function separator(int $maxWidth, string $char = '-'): string
    {
        return str_repeat($char, $maxWidth) . "\n";
    }

    /**
     * Generar bytes ESC/POS para Comanda de Cocina (KDS / Impresora Térmica de Cocina).
     */
    public static function buildKitchenTicket(Order $order, int $widthMm = 80): string
    {
        // Solo ítems que requieran preparación en cocina (excluir refrescos/bebidas directas que no van a cocina)
        $kitchenItems = $order->items->filter(function ($item) {
            if (!$item->product) {
                return false;
            }
            return (bool) ($item->product->send_to_kitchen ?? ($item->product->type === 'dish'));
        });

        if ($kitchenItems->isEmpty()) {
            return '';
        }

        $w = self::getColumnWidth($widthMm);
        $out = self::INIT_PRINTER;

        // Encabezado Cocina
        $out .= self::ALIGN_CENTER . self::DOUBLE_BOTH_ON . self::BOLD_ON;
        $out .= "*** ORDEN DE COCINA ***\n";
        $out .= self::NORMAL_TEXT . self::BOLD_OFF;

        $tableName = $order->diningTable ? $order->diningTable->name : 'PARA LLEVAR / DELIVERY';
        $out .= self::ALIGN_CENTER . self::DOUBLE_HEIGHT_ON . self::BOLD_ON;
        $out .= "{$tableName}\n";
        if (!empty($order->account_label) && $order->account_label !== 'Cuenta Principal') {
            $out .= "({$order->account_label})\n";
        }
        $out .= self::NORMAL_TEXT . self::BOLD_OFF;

        $out .= self::ALIGN_LEFT;
        $out .= "Orden: #{$order->order_number}\n";
        $out .= "Mesonero: " . ($order->user ? $order->user->name : 'N/A') . "\n";
        $out .= "Hora: " . now()->format('d/m/Y h:i A') . "\n";
        $out .= self::separator($w, '=');

        // Ítems de comanda
        $out .= self::BOLD_ON;
        $out .= self::formatTwoColumns("CANT  DESCRIPCIÓN", "ESTADO", $w);
        $out .= self::BOLD_OFF;
        $out .= self::separator($w, '-');

        foreach ($kitchenItems as $item) {
            $prodName = $item->product ? $item->product->name : 'Producto';
            $qtyText = "{$item->quantity}x";

            $out .= self::BOLD_ON . self::DOUBLE_HEIGHT_ON;
            $out .= "{$qtyText} {$prodName}\n";
            $out .= self::NORMAL_TEXT . self::BOLD_OFF;

            // Notas y Modificadores resaltados
            if (!empty($item->notes)) {
                $out .= self::BOLD_ON;
                $out .= "  >> NOTA: {$item->notes}\n";
                $out .= self::BOLD_OFF;
            }

            if (!empty($item->modifiers_data) && is_array($item->modifiers_data)) {
                foreach ($item->modifiers_data as $mod) {
                    $modName = is_array($mod) ? ($mod['name'] ?? '') : $mod;
                    if ($modName) {
                        $out .= "  + {$modName}\n";
                    }
                }
            }
        }

        $out .= self::separator($w, '=');
        if (!empty($order->notes)) {
            $out .= self::BOLD_ON . "NOTAS DE ORDEN:\n" . self::BOLD_OFF;
            $out .= "{$order->notes}\n";
            $out .= self::separator($w, '-');
        }

        $out .= "\n\n\n";
        $out .= self::FEED_AND_CUT;

        return self::encodeCp850($out);
    }

    /**
     * Generar bytes ESC/POS para Pre-cuenta (entregar al cliente en mesa).
     */
    public static function buildPreBillTicket(Order $order, int $widthMm = 80): string
    {
        $w = self::getColumnWidth($widthMm);
        $config = Configuration::first();
        $businessName = $config->business_name ?? 'JSFood Restaurante';

        $out = self::INIT_PRINTER;
        $out .= self::ALIGN_CENTER . self::BOLD_ON;
        $out .= "{$businessName}\n";
        $out .= "=== PRE-CUENTA ===\n";
        $out .= self::BOLD_OFF;

        $tableName = $order->diningTable ? $order->diningTable->name : 'Mostrador / Llevar';
        $out .= self::DOUBLE_HEIGHT_ON . "{$tableName}\n" . self::NORMAL_TEXT;
        if (!empty($order->account_label)) {
            $out .= "Sub-cuenta: {$order->account_label}\n";
        }

        $out .= self::ALIGN_LEFT;
        $out .= "Orden: #{$order->order_number}\n";
        $out .= "Fecha: " . now()->format('d/m/Y h:i A') . "\n";
        $out .= "Atendido por: " . ($order->user ? $order->user->name : 'Mesonero') . "\n";
        $out .= self::separator($w, '-');

        // Ítems y precios
        $out .= self::BOLD_ON;
        $out .= self::formatTwoColumns("CANT DESCRIPCION", "TOTAL ($)", $w);
        $out .= self::BOLD_OFF;
        $out .= self::separator($w, '-');

        foreach ($order->items as $item) {
            $prodName = $item->product ? $item->product->name : 'Producto';
            $qtyDesc = "{$item->quantity}x " . $prodName;
            $priceFormatted = "$" . number_format($item->subtotal_usd, 2);

            $out .= self::formatTwoColumns($qtyDesc, $priceFormatted, $w);
            if (!empty($item->notes)) {
                $out .= "  * {$item->notes}\n";
            }
        }

        $out .= self::separator($w, '=');
        $out .= self::BOLD_ON;
        $out .= self::formatTwoColumns("SUBTOTAL:", "$" . number_format($order->subtotal_usd, 2), $w);

        if ($order->discount_usd > 0) {
            $out .= self::formatTwoColumns("DESCUENTO:", "-$" . number_format($order->discount_usd, 2), $w);
        }

        $out .= self::DOUBLE_HEIGHT_ON;
        $out .= self::formatTwoColumns("TOTAL A PAGAR:", "$" . number_format($order->total_usd, 2), $w);
        $out .= self::NORMAL_TEXT . self::BOLD_OFF;

        // Desglose Multimoneda si está activo
        if (!CurrencyHelper::isSingleCurrencyMode()) {
            $out .= self::separator($w, '-');
            $out .= self::ALIGN_CENTER . self::BOLD_ON . "EQUIVALENCIAS DE PAGO:\n" . self::BOLD_OFF . self::ALIGN_LEFT;

            if ($config && $config->bcv_rate > 1) {
                $totalVed = $order->total_usd * $config->bcv_rate;
                $out .= self::formatTwoColumns("Total Bolívares (BCV):", "Bs " . number_format($totalVed, 2), $w);
            }

            if ($config && $config->cop_rate > 1) {
                $totalCop = $order->total_usd * $config->cop_rate;
                $out .= self::formatTwoColumns("Total Pesos (COP):", "COP$ " . number_format($totalCop, 0), $w);
            }
        }

        $out .= self::separator($w, '-');
        $out .= self::ALIGN_CENTER;
        $out .= "DOCUMENTO NO FISCAL - PRE-CUENTA\n";
        $out .= "Propina voluntaria no incluida\n";
        $out .= "¡Gracias por su visita!\n";
        $out .= "\n\n\n" . self::FEED_AND_CUT;

        return self::encodeCp850($out);
    }

    /**
     * Generar bytes ESC/POS para Ticket de Factura / Cobro en Caja.
     */
    public static function buildReceiptTicket(Order $order, int $widthMm = 80, bool $openDrawer = true): string
    {
        $w = self::getColumnWidth($widthMm);
        $config = Configuration::first();
        $businessName = $config->business_name ?? 'JSFood Restaurante';
        $taxId = $config->taxpayer_id ?? '';
        $phone = $config->phone ?? '';
        $address = $config->address ?? '';

        $out = self::INIT_PRINTER;

        if ($openDrawer) {
            $out .= self::OPEN_DRAWER;
        }

        // Encabezado
        $out .= self::ALIGN_CENTER . self::BOLD_ON . self::DOUBLE_HEIGHT_ON;
        $out .= "{$businessName}\n";
        $out .= self::NORMAL_TEXT . self::BOLD_OFF;

        if ($taxId) $out .= "RIF/NIT: {$taxId}\n";
        if ($phone) $out .= "Tel: {$phone}\n";
        if ($address) $out .= "{$address}\n";

        $out .= self::BOLD_ON . "COMPROBANTE DE PAGO\n" . self::BOLD_OFF;
        $out .= self::separator($w, '=');

        $out .= self::ALIGN_LEFT;
        $out .= "Factura / Ticket: #{$order->order_number}\n";
        $out .= "Fecha: " . ($order->closed_at ? $order->closed_at->format('d/m/Y h:i A') : now()->format('d/m/Y h:i A')) . "\n";
        $out .= "Mesa: " . ($order->diningTable ? $order->diningTable->name : 'Directa') . "\n";
        if (!empty($order->account_label)) {
            $out .= "Cuenta: {$order->account_label}\n";
        }
        $out .= "Cajero: " . ($order->cashier ? $order->cashier->name : (auth()->user()?->name ?? 'Caja')) . "\n";
        $out .= "Cliente: " . ($order->customer ? $order->customer->name : 'Consumidor Final') . "\n";
        $out .= self::separator($w, '-');

        // Detalle de ítems
        $out .= self::BOLD_ON;
        $out .= self::formatTwoColumns("CANT DESCRIPCION", "TOTAL ($)", $w);
        $out .= self::BOLD_OFF;
        $out .= self::separator($w, '-');

        foreach ($order->items as $item) {
            $prodName = $item->product ? $item->product->name : 'Producto';
            $qtyDesc = "{$item->quantity}x " . $prodName;
            $out .= self::formatTwoColumns($qtyDesc, "$" . number_format($item->subtotal_usd, 2), $w);
        }

        $out .= self::separator($w, '=');
        if ($order->discount_usd > 0) {
            $out .= self::formatTwoColumns("SUBTOTAL:", "$" . number_format($order->subtotal_usd, 2), $w);
            $out .= self::formatTwoColumns("DESCUENTO:", "-$" . number_format($order->discount_usd, 2), $w);
        }
        $out .= self::BOLD_ON . self::DOUBLE_HEIGHT_ON;
        $out .= self::formatTwoColumns("TOTAL PAGADO:", "$" . number_format($order->total_usd, 2), $w);
        $out .= self::NORMAL_TEXT . self::BOLD_OFF;

        // Desglose de Métodos de Pago
        if ($order->payments->isNotEmpty()) {
            $out .= self::separator($w, '-');
            $out .= self::BOLD_ON . "METODOS DE PAGO RECIBIDOS:\n" . self::BOLD_OFF;
            foreach ($order->payments as $p) {
                $methodLabel = match ($p->payment_method) {
                    'cash_usd' => 'Efectivo USD ($)',
                    'cash_ved' => 'Efectivo Bs (VED)',
                    'cash_cop' => 'Efectivo Pesos (COP)',
                    'card' => 'Punto de Venta / Tarjeta',
                    'mobile_pay' => 'Pago Móvil',
                    'zelle' => 'Zelle',
                    'binance_usdt' => 'Binance USDT',
                    'credit' => 'Cargado a Crédito',
                    default => strtoupper($p->payment_method),
                };

                $amountTxt = "{$p->currency_code} " . number_format($p->amount_currency, 2);
                $out .= self::formatTwoColumns($methodLabel, $amountTxt, $w);
            }
        }

        $out .= self::separator($w, '-');
        $out .= self::ALIGN_CENTER;
        $out .= "¡Muchas gracias por su preferencia!\n";
        $out .= "Conserve este ticket como comprobante\n";
        $out .= "\n\n\n" . self::FEED_AND_CUT;

        return self::encodeCp850($out);
    }

    /**
     * Enviar datos crudos a una impresora térmica configurada.
     */
    public static function sendToPrinter(string $rawEscposData, ?string $printerTarget = null): bool
    {
        if (empty($printerTarget)) {
            $config = Configuration::first();
            $printerTarget = $config->printer_name ?? 'POS-80';
        }

        // Si el destino es una ruta UNC con IP (ej: \\192.168.20.117\POS-80-Print), resolver IP al nombre NetBIOS
        if (str_starts_with($printerTarget, '\\\\')) {
            $parts = explode('\\', ltrim($printerTarget, '\\'));
            if (count($parts) >= 2) {
                $host = $parts[0];
                $share = $parts[1];
                if (filter_var($host, FILTER_VALIDATE_IP)) {
                    $resolvedHost = CustomWindowsPrintConnector::resolveIpToHostname($host);
                    if ($resolvedHost && strtolower($resolvedHost) !== strtolower($host)) {
                        $printerTarget = "\\\\{$resolvedHost}\\{$share}";
                    }
                }
            }
        }

        try {
            $connector = new CustomWindowsPrintConnector($printerTarget);
            $connector->write($rawEscposData);
            $connector->finalize();
            return true;
        } catch (\Throwable $e) {
            Log::error("Fallo al imprimir en {$printerTarget}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Convertir texto UTF-8 a CP850 para que la impresora térmica imprima acentos y caracteres latinos correctamente.
     */
    private static function encodeCp850(string $text): string
    {
        $converted = @iconv('UTF-8', 'CP850//TRANSLIT', $text);
        return $converted ?: $text;
    }
}
