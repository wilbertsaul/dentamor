<?php

namespace App\Services;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Illuminate\Support\Facades\Storage;

class PdfService
{
    public static function generate(Invoice $invoice): string
    {
        $invoice->load('company', 'client', 'items');

        $qrData = static::buildQrData($invoice);
        $qrPng = static::generateQrPng($qrData);

        $logoBase64 = null;
        if ($invoice->company && $invoice->company->logo_path && Storage::disk('local')->exists($invoice->company->logo_path)) {
            $logoContent = Storage::disk('local')->get($invoice->company->logo_path);
            $mime = mime_content_type(storage_path('app/' . $invoice->company->logo_path));
            $logoBase64 = 'data:' . $mime . ';base64,' . base64_encode($logoContent);
        }

        $pdf = Pdf::loadView('pdfs.invoice', [
            'invoice' => $invoice,
            'qrCodeBase64' => base64_encode($qrPng),
            'logoBase64' => $logoBase64,
        ]);

        $pdf->setPaper('A4');
        $filename = $invoice->serie . '-' . $invoice->number . '.pdf';
        $path = 'sunat/pdf/' . $filename;
        Storage::disk('local')->put($path, $pdf->output());

        $invoice->update(['pdf_path' => $path]);

        return $path;
    }

    protected static function buildQrData(Invoice $invoice): string
    {
        $company = $invoice->company;
        $client = $invoice->client;

        $docTypeMap = ['DNI' => '1', 'RUC' => '6', 'CE' => '4'];

        $parts = [
            $company->ruc,
            $invoice->invoice_type === 'F' ? '01' : '03',
            $invoice->serie,
            $invoice->number,
            number_format($invoice->igv, 2, '.', ''),
            number_format($invoice->total, 2, '.', ''),
            $invoice->issue_date->format('Y-m-d'),
            $client ? ($docTypeMap[$client->doc_type] ?? '1') : '-',
            $client ? $client->doc_number : '00000000',
        ];

        return implode('|', $parts);
    }

    protected static function generateQrPng(string $data): string
    {
        $result = Builder::create()
            ->writer(new PngWriter())
            ->data($data)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::Medium)
            ->size(150)
            ->margin(0)
            ->build();

        return $result->getString();
    }
}
