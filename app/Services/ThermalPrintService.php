<?php

namespace App\Services;

use App\Models\Order;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\DummyPrintConnector;

class ThermalPrintService
{
    /**
     * Generate raw ESC/POS binary commands for receipt layout.
     * 
     * @param Order $order
     * @return string Raw binary data
     */
    public function generateReceiptBytes(Order $order): string
    {
        $connector = new DummyPrintConnector();
        $printer = new Printer($connector);

        // 1. Initialize printer: \x1B\x40
        $printer->initialize();

        // 2. Bold & Center Align Header
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH | Printer::MODE_DOUBLE_HEIGHT | Printer::MODE_EMPHASIZED);
        $printer->text("AL-MADINA POS\n");
        
        $printer->selectPrintMode(Printer::MODE_FONT_A);
        $printer->text($order->location->name . "\n");
        $printer->text("Tel: +218 91-0000000\n");
        $printer->text("--------------------------------\n");

        // Order metadata
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("Order: " . substr($order->id, 0, 8) . "\n");
        $printer->text("Date: " . $order->created_at->format('Y-m-d H:i:s') . "\n");
        $printer->text("Status: " . strtoupper($order->status) . "\n");
        $printer->text("Payment: " . strtoupper($order->payment_status) . "\n");
        $printer->text("--------------------------------\n");

        if ($order->notes) {
            $printer->text("Notes: " . $order->notes . "\n");
            $printer->text("--------------------------------\n");
        }

        // 3. Itemized Receipt Table
        // Total column space = 32 chars (Item: 16 chars, Qty: 6 chars, Price: 10 chars)
        $printer->text(
            str_pad("Item", 16) . 
            str_pad("Qty", 6, " ", STR_PAD_LEFT) . 
            str_pad("Price", 10, " ", STR_PAD_LEFT) . "\n"
        );
        $printer->text("--------------------------------\n");

        foreach ($order->items as $item) {
            $name = substr($item->product->name, 0, 15);
            $qty = (string)$item->quantity;
            $price = number_format($item->price * $item->quantity, 2);

            $printer->text(
                str_pad($name, 16) . 
                str_pad($qty, 6, " ", STR_PAD_LEFT) . 
                str_pad($price, 10, " ", STR_PAD_LEFT) . "\n"
            );
        }

        $printer->text("--------------------------------\n");

        // Totals
        $subtotal = number_format($order->total_amount + $order->discount - $order->tax, 2);
        $discount = number_format($order->discount, 2);
        $tax = number_format($order->tax, 2);
        $total = number_format($order->total_amount, 2);

        $printer->text(str_pad("Subtotal:", 20) . str_pad($subtotal, 12, " ", STR_PAD_LEFT) . "\n");
        if ($order->discount > 0) {
            $printer->text(str_pad("Discount:", 20) . str_pad("-" . $discount, 12, " ", STR_PAD_LEFT) . "\n");
        }
        $printer->text(str_pad("Tax:", 20) . str_pad($tax, 12, " ", STR_PAD_LEFT) . "\n");
        
        $printer->selectPrintMode(Printer::MODE_EMPHASIZED);
        $printer->text(str_pad("TOTAL (LYD):", 20) . str_pad($total, 12, " ", STR_PAD_LEFT) . "\n");
        $printer->selectPrintMode(Printer::MODE_FONT_A);
        
        $printer->text("--------------------------------\n");

        // Footer
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("شكراً لزيارتكم!\n");
        $printer->text("Thank you for your patronage!\n");
        $printer->feed(2);

        // 4. Pulse cash drawer kick: \x1B\x70\x00\x19\xFA
        $connector->write("\x1B\x70\x00\x19\xFA");

        // 5. Auto-cutter command: \x1D\x56\x42\x00
        $printer->feed(1);
        $connector->write("\x1D\x56\x42\x00");

        $printer->close();

        return $connector->getData();
    }

    /**
     * Send raw receipt bytes directly to a local LAN printer on port 9100.
     * 
     * @param string $ipAddress
     * @param Order $order
     * @return bool
     */
    public function printToNetwork(string $ipAddress, Order $order): bool
    {
        try {
            $bytes = $this->generateReceiptBytes($order);
            
            // Open a direct TCP socket on port 9100 with a 3-second timeout
            $socket = @fsockopen($ipAddress, 9100, $errno, $errstr, 3);
            if (!$socket) {
                throw new \Exception("TCP connection to $ipAddress:9100 failed: $errstr ($errno)");
            }
            
            fwrite($socket, $bytes);
            fclose($socket);
            
            return true;
        } catch (\Exception $e) {
            \Log::error("Direct LAN Print failed: " . $e->getMessage());
            return false;
        }
    }
}
