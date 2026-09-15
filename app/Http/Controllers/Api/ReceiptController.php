<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Pdf\ReceiptPdfService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ReceiptController extends Controller
{
    public function __construct(
        protected ReceiptPdfService $pdfService
    ) {}

    /**
     * Download or stream thermal receipt (58mm or 80mm).
     */
    public function thermal(Request $request, Order $order): Response
    {
        $paperWidth = (int) $request->query('width', 58);
        if (!in_array($paperWidth, [58, 80], true)) {
            $paperWidth = 58;
        }

        $pdf = $this->pdfService->generateThermalPdf($order, $paperWidth);
        $filename = "struk_{$order->invoice_number}.pdf";

        return $pdf->stream($filename);
    }

    /**
     * Download or stream formal A4 invoice.
     */
    public function invoice(Order $order): Response
    {
        $pdf = $this->pdfService->generateInvoicePdf($order);
        $filename = "faktur_{$order->invoice_number}.pdf";

        return $pdf->stream($filename);
    }
}
