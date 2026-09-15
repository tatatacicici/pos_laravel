<?php

declare(strict_types=1);

namespace App\Services\Pdf;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPDF;

class ReceiptPdfService
{
    /**
     * Generate 58mm or 80mm thermal receipt PDF.
     * Dimensions are converted to points (1 mm = 2.83465 pt).
     */
    public function generateThermalPdf(Order $order, int $paperWidthMm = 58): DomPDF
    {
        $order->loadMissing(['outlet', 'cashier', 'items.product', 'latestPayment']);

        // Estimate receipt height dynamically based on item count
        $baseHeight = 120; // header + footer
        $itemHeight = count($order->items) * 22;
        $totalHeightMm = max(180, $baseHeight + $itemHeight);

        // Convert mm to points
        $widthPt = $paperWidthMm * 2.83465;
        $heightPt = $totalHeightMm * 2.83465;

        $pdf = Pdf::loadView('receipts.thermal', [
            'order' => $order,
            'outlet' => $order->outlet,
            'paperWidthMm' => $paperWidthMm,
        ]);

        $pdf->setPaper([0, 0, $widthPt, $heightPt], 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'Courier',
        ]);

        return $pdf;
    }

    /**
     * Generate standard A4 formal sales invoice PDF.
     */
    public function generateInvoicePdf(Order $order): DomPDF
    {
        $order->loadMissing(['outlet', 'cashier', 'items.product', 'latestPayment']);

        $pdf = Pdf::loadView('receipts.invoice_a4', [
            'order' => $order,
            'outlet' => $order->outlet,
        ]);

        $pdf->setPaper('a4', 'portrait');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'sans-serif',
        ]);

        return $pdf;
    }
}
