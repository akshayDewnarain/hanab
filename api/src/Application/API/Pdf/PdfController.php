<?php

namespace Application\API\Pdf;

use App\Pdf\BrowsershotConfig;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Spatie\LaravelPdf\Facades\Pdf;

class PdfController extends Controller
{
    public function downloadInvoice(Request $request, BrowsershotConfig $browsershotConfig)
    {
        $data = $request->validate([
            'invoice_number' => 'required|string|max:255',
            'invoice_lines' => 'required|array',
            'invoice_lines.*.description' => 'required|string',
            'invoice_lines.*.hours' => 'required|numeric',
            'invoice_lines.*.hourly_rate' => 'required|numeric',
            'charge_dutch_vat' => 'sometimes|boolean',
            'trn' => 'sometimes|string|max:255',
        ]);

        $invoiceNumber = $data['invoice_number'];
        $invoiceLines = $data['invoice_lines'];
        $chargeDutchVat = $data['charge_dutch_vat'] ?? true;
        $trn = $data['trn'] ?? null;

        return Pdf::view('pdf.invoice', [
            'invoiceNumber' => $invoiceNumber,
            'invoiceLines' => $invoiceLines,
            'chargeDutchVat' => $chargeDutchVat,
            'trn' => $trn,
        ])
            ->withBrowsershot($browsershotConfig->closure())
            ->format('A4')
            ->download('invoice-'.$invoiceNumber.'.pdf');
    }
}
